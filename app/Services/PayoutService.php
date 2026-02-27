<?php

namespace App\Services;

use App\Models\OrderStatusHistory;
use App\Models\ProductOrder;
use App\Models\SellerPayout;
use App\Models\TaxLedger;
use App\Models\Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    /**
     * Process payouts for all eligible orders.
     * Typically called via scheduled command (e.g., daily or on payout day).
     */
    public static function processEligiblePayouts(): array
    {
        $settings = DB::table('tbl_settings')->first();
        $holdDays = $settings->payout_hold_days ?? 7;
        $minPayoutPaise = $settings->min_payout_amount_paise ?? 10000; // ₹100

        // Find delivered orders past return window that haven't been paid out
        $eligibleOrders = ProductOrder::where('status', ProductOrder::STATUS_DELIVERED)
            ->where('payout_eligible', false)
            ->whereNotNull('return_window_expires_at')
            ->where('return_window_expires_at', '<', now())
            ->whereDoesntHave('returns', function ($q) {
                $q->whereNotIn('status', [
                    \App\Models\ProductReturn::STATUS_REJECTED,
                    \App\Models\ProductReturn::STATUS_INSPECTION_FAILED,
                    \App\Models\ProductReturn::STATUS_REFUND_COMPLETED,
                ]);
            })
            ->get();

        // Mark as payout eligible
        foreach ($eligibleOrders as $order) {
            $order->update(['payout_eligible' => true]);
        }

        // Group eligible unpaid orders by seller
        $pendingOrders = ProductOrder::where('payout_eligible', true)
            ->whereNull('payout_id')
            ->where('seller_net_amount_paise', '>', 0)
            ->get()
            ->groupBy('seller_id');

        $payoutsCreated = [];

        foreach ($pendingOrders as $sellerId => $orders) {
            $totalAmount = $orders->sum('seller_net_amount_paise');

            // Skip if below minimum payout
            if ($totalAmount < $minPayoutPaise) {
                continue;
            }

            $totalCommission = $orders->sum('platform_commission_paise');
            $totalTcs = $orders->sum('tcs_amount_paise');
            $totalTds = $orders->sum('tds_amount_paise');
            $orderIds = $orders->pluck('id')->toArray();

            try {
                $payout = DB::transaction(function () use ($sellerId, $totalAmount, $totalCommission, $totalTcs, $totalTds, $orderIds, $orders) {
                    $payout = SellerPayout::create([
                        'seller_id' => $sellerId,
                        'amount_paise' => $totalAmount,
                        'commission_deducted_paise' => $totalCommission,
                        'tcs_deducted_paise' => $totalTcs,
                        'tds_deducted_paise' => $totalTds,
                        'order_ids' => $orderIds,
                        'status' => SellerPayout::STATUS_PENDING,
                    ]);

                    // Link orders to payout
                    ProductOrder::whereIn('id', $orderIds)->update(['payout_id' => $payout->id]);

                    // Credit seller wallet
                    Users::where('id', $sellerId)->increment('seller_wallet_paise', $totalAmount);
                    Users::where('id', $sellerId)->increment('seller_total_earned_paise', $totalAmount);

                    return $payout;
                });

                $payoutsCreated[] = $payout;
            } catch (\Exception $e) {
                Log::error("Payout processing failed for seller {$sellerId}", ['error' => $e->getMessage()]);
            }
        }

        return $payoutsCreated;
    }

    /**
     * Calculate the financial breakdown for an order.
     * Called during checkout to determine commission, taxes, and seller net.
     */
    public static function calculateOrderFinancials(ProductOrder $order, float $commissionRate): array
    {
        $settings = DB::table('tbl_settings')->first();
        $totalAmount = $order->total_amount_paise;

        // Platform commission
        $commissionPaise = (int) round($totalAmount * ($commissionRate / 100));

        // TCS: 1% on total sale amount (Section 52 CGST Act)
        $tcsRate = (float) ($settings->tcs_rate ?? 1.0);
        $tcsPaise = (int) round($totalAmount * ($tcsRate / 100));

        // TDS: 1% under Section 194-O (only if seller's annual sale > threshold)
        $tdsRate = (float) ($settings->tds_rate ?? 1.0);
        $tdsThreshold = (int) ($settings->tds_threshold_paise ?? 50000000); // ₹5L default
        $sellerAnnualSales = ProductOrder::where('seller_id', $order->seller_id)
            ->where('status', ProductOrder::STATUS_DELIVERED)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount_paise');

        $tdsPaise = 0;
        if (($sellerAnnualSales + $totalAmount) > $tdsThreshold) {
            $tdsPaise = (int) round($totalAmount * ($tdsRate / 100));
        }

        // Seller net = Total - Commission - TCS - TDS
        $sellerNetPaise = $totalAmount - $commissionPaise - $tcsPaise - $tdsPaise;

        // GST on items
        $gstPaise = $order->gst_amount_paise ?? 0;

        return [
            'total_amount_paise' => $totalAmount,
            'platform_commission_rate' => $commissionRate,
            'platform_commission_paise' => $commissionPaise,
            'tcs_amount_paise' => $tcsPaise,
            'tds_amount_paise' => $tdsPaise,
            'seller_net_amount_paise' => $sellerNetPaise,
            'gst_amount_paise' => $gstPaise,
        ];
    }

    /**
     * Record tax entries for an order.
     */
    public static function recordOrderTaxes(ProductOrder $order): void
    {
        if ($order->tcs_amount_paise > 0) {
            TaxLedger::record(
                $order->seller_id,
                $order->id,
                TaxLedger::TYPE_TCS,
                $order->tcs_amount_paise,
                "TCS on Order #{$order->id}"
            );
        }

        if ($order->tds_amount_paise > 0) {
            TaxLedger::record(
                $order->seller_id,
                $order->id,
                TaxLedger::TYPE_TDS,
                $order->tds_amount_paise,
                "TDS on Order #{$order->id}"
            );
        }
    }

    /**
     * Fetch payout history for a seller.
     */
    public static function fetchSellerPayouts(int $sellerId, int $limit = 20, ?int $lastItemId = null): \Illuminate\Support\Collection
    {
        $query = SellerPayout::where('seller_id', $sellerId)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($lastItemId) {
            $query->where('id', '<', $lastItemId);
        }

        return $query->get();
    }

    /**
     * Get seller earnings summary.
     */
    public static function getSellerEarningsSummary(int $sellerId): array
    {
        $seller = Users::find($sellerId);

        $totalEarned = $seller->seller_total_earned_paise ?? 0;
        $currentWallet = $seller->seller_wallet_paise ?? 0;

        // Pending payout (eligible but not yet paid)
        $pendingPayout = ProductOrder::where('seller_id', $sellerId)
            ->where('payout_eligible', true)
            ->whereNull('payout_id')
            ->sum('seller_net_amount_paise');

        // In hold (delivered but return window still open)
        $inHold = ProductOrder::where('seller_id', $sellerId)
            ->where('status', ProductOrder::STATUS_DELIVERED)
            ->where('payout_eligible', false)
            ->sum('seller_net_amount_paise');

        // This month's earnings
        $thisMonth = ProductOrder::where('seller_id', $sellerId)
            ->where('status', ProductOrder::STATUS_DELIVERED)
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->sum('seller_net_amount_paise');

        // Total orders
        $totalOrders = ProductOrder::where('seller_id', $sellerId)
            ->where('status', ProductOrder::STATUS_DELIVERED)
            ->count();

        // Tax deducted this FY
        $currentFY = TaxLedger::currentFY();
        $totalTcs = TaxLedger::where('seller_id', $sellerId)
            ->where('tax_type', TaxLedger::TYPE_TCS)
            ->where('financial_year', $currentFY)
            ->sum('amount_paise');
        $totalTds = TaxLedger::where('seller_id', $sellerId)
            ->where('tax_type', TaxLedger::TYPE_TDS)
            ->where('financial_year', $currentFY)
            ->sum('amount_paise');

        return [
            'total_earned_paise' => $totalEarned,
            'wallet_balance_paise' => $currentWallet,
            'pending_payout_paise' => $pendingPayout,
            'in_hold_paise' => $inHold,
            'this_month_paise' => $thisMonth,
            'total_orders_delivered' => $totalOrders,
            'tcs_deducted_fy_paise' => $totalTcs,
            'tds_deducted_fy_paise' => $totalTds,
            'financial_year' => $currentFY,
        ];
    }
}
