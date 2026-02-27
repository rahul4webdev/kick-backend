<?php

namespace App\Http\Controllers;

use App\Models\AffiliateEarning;
use App\Models\AffiliateLink;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AffiliateController extends Controller
{
    // ─── Browse Products Available for Affiliate ────────────────────

    public function fetchAffiliateProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $limit = $request->limit ?? 20;

        $query = Product::where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->where('affiliate_enabled', true)
            ->where('seller_id', '!=', $user->id);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->search) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }
        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $products = $query->with(['seller:id,username,fullname,profile_photo,is_verify', 'category:id,name'])
            ->orderByDesc('sold_count')
            ->limit($limit)
            ->get();

        // Mark which ones the user already has affiliate links for
        $existingLinks = AffiliateLink::where('creator_id', $user->id)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('product_id')
            ->toArray();

        $products->each(function ($product) use ($existingLinks) {
            $product->has_affiliate_link = in_array($product->id, $existingLinks);
        });

        return [
            'status' => true,
            'message' => '',
            'data' => $products,
        ];
    }

    // ─── Create Affiliate Link ─────────────────────────────────────

    public function createAffiliateLink(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $product = Product::where('id', $request->product_id)
            ->where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->where('affiliate_enabled', true)
            ->first();

        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not available for affiliate');
        }

        if ($product->seller_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot create affiliate link for your own product');
        }

        // Check if link already exists
        $existing = AffiliateLink::where('creator_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'You already have an affiliate link for this product');
        }

        $link = AffiliateLink::create([
            'creator_id' => $user->id,
            'product_id' => $product->id,
            'affiliate_code' => AffiliateLink::generateCode(),
            'commission_rate' => $product->affiliate_commission_rate,
            'status' => AffiliateLink::STATUS_ACTIVE,
        ]);

        $link->load(['product:id,name,price_coins,images,seller_id', 'product.seller:id,username,fullname,profile_photo,is_verify']);

        return [
            'status' => true,
            'message' => 'Affiliate link created',
            'data' => $link,
        ];
    }

    // ─── Remove Affiliate Link ─────────────────────────────────────

    public function removeAffiliateLink(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $link = AffiliateLink::where('id', $request->link_id)
            ->where('creator_id', $user->id)
            ->first();

        if (!$link) {
            return GlobalFunction::sendSimpleResponse(false, 'Affiliate link not found');
        }

        $link->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Affiliate link removed');
    }

    // ─── Fetch My Affiliate Links ──────────────────────────────────

    public function fetchMyAffiliateLinks(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $links = AffiliateLink::where('creator_id', $user->id)
            ->with(['product:id,name,price_coins,images,seller_id,sold_count,avg_rating', 'product.seller:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('total_earnings')
            ->get();

        return [
            'status' => true,
            'message' => '',
            'data' => $links,
        ];
    }

    // ─── Fetch Affiliate Earnings ──────────────────────────────────

    public function fetchAffiliateEarnings(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $limit = $request->limit ?? 20;

        $query = AffiliateEarning::whereHas('affiliateLink', function ($q) use ($user) {
            $q->where('creator_id', $user->id);
        })
            ->with([
                'affiliateLink.product:id,name,price_coins,images',
                'order:id,buyer_id,quantity,total_coins',
                'order.buyer:id,username,fullname,profile_photo',
            ])
            ->orderByDesc('id');

        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $earnings = $query->limit($limit)->get();

        return [
            'status' => true,
            'message' => '',
            'data' => $earnings,
        ];
    }

    // ─── Affiliate Dashboard ───────────────────────────────────────

    public function fetchAffiliateDashboard(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $linkIds = AffiliateLink::where('creator_id', $user->id)->pluck('id');

        $totalLinks = $linkIds->count();
        $totalEarnings = AffiliateEarning::whereIn('affiliate_link_id', $linkIds)->sum('commission_coins');
        $totalPurchases = AffiliateEarning::whereIn('affiliate_link_id', $linkIds)->count();
        $totalClicks = AffiliateLink::where('creator_id', $user->id)->sum('click_count');

        // Last 30 days earnings
        $last30Days = AffiliateEarning::whereIn('affiliate_link_id', $linkIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('commission_coins');

        // Top earning products
        $topProducts = AffiliateLink::where('creator_id', $user->id)
            ->where('total_earnings', '>', 0)
            ->with(['product:id,name,price_coins,images'])
            ->orderByDesc('total_earnings')
            ->limit(5)
            ->get(['id', 'product_id', 'total_earnings', 'purchase_count', 'click_count', 'commission_rate']);

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'total_links' => $totalLinks,
                'total_earnings' => (int) $totalEarnings,
                'total_purchases' => $totalPurchases,
                'total_clicks' => (int) $totalClicks,
                'last_30_days_earnings' => (int) $last30Days,
                'top_products' => $topProducts,
            ],
        ];
    }

    // ─── Track Affiliate Click ─────────────────────────────────────

    public function trackAffiliateClick(Request $request)
    {
        $link = AffiliateLink::where('affiliate_code', $request->affiliate_code)
            ->where('status', AffiliateLink::STATUS_ACTIVE)
            ->first();

        if ($link) {
            $link->increment('click_count');
        }

        return GlobalFunction::sendSimpleResponse(true, 'ok');
    }

    // ─── Process Affiliate Commission (called from purchaseProduct) ─

    public static function processAffiliateCommission($order, $affiliateCode)
    {
        if (empty($affiliateCode)) return;

        $link = AffiliateLink::where('affiliate_code', $affiliateCode)
            ->where('status', AffiliateLink::STATUS_ACTIVE)
            ->where('product_id', $order->product_id)
            ->first();

        if (!$link) return;

        // Don't pay commission if buyer is the affiliate creator
        if ($link->creator_id == $order->buyer_id) return;

        // Calculate commission
        $commissionCoins = (int) floor($order->total_coins * ($link->commission_rate / 100));
        if ($commissionCoins < 1) return;

        // Create earning record
        AffiliateEarning::create([
            'affiliate_link_id' => $link->id,
            'order_id' => $order->id,
            'commission_coins' => $commissionCoins,
            'status' => 1, // paid instantly
            'created_at' => now(),
        ]);

        // Update link stats
        $link->increment('purchase_count');
        $link->increment('total_earnings', $commissionCoins);

        // Credit coins to affiliate creator
        $creator = \App\Models\Users::find($link->creator_id);
        if ($creator) {
            $creator->coin_wallet += $commissionCoins;
            $creator->coin_collected_lifetime += $commissionCoins;
            $creator->save();

            \App\Models\CoinTransaction::create([
                'user_id' => $creator->id,
                'type' => Constants::txnAffiliateEarning,
                'coins' => $commissionCoins,
                'direction' => Constants::credit,
                'related_user_id' => $order->buyer_id,
                'reference_id' => $order->id,
                'note' => "Affiliate commission for: " . ($link->product->name ?? 'Product'),
            ]);
        }
    }

    // ─── Admin: List Affiliate Links ───────────────────────────────

    public function listAffiliateLinks_Admin(Request $request)
    {
        $totalData = AffiliateLink::count();
        $totalFiltered = $totalData;
        $limit = $request->input('length');
        $start = $request->input('start');

        $query = AffiliateLink::with(['creator:id,username,fullname,profile_photo', 'product:id,name,price_coins,images,seller_id']);

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->whereHas('creator', function ($q) use ($search) {
                $q->where('username', 'ilike', "%$search%");
            })->orWhereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%$search%");
            })->orWhere('affiliate_code', 'ilike', "%$search%");
            $totalFiltered = $query->count();
        }

        $links = $query->orderByDesc('id')->offset($start)->limit($limit)->get();

        $data = [];
        foreach ($links as $i => $link) {
            $data[] = [
                $start + $i + 1,
                $link->creator->username ?? '-',
                $link->product->name ?? '-',
                $link->affiliate_code,
                $link->commission_rate . '%',
                $link->click_count,
                $link->purchase_count,
                $link->total_earnings . ' coins',
                $link->status == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Paused</span>',
                $link->created_at?->format('Y-m-d'),
            ];
        }

        return [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ];
    }
}
