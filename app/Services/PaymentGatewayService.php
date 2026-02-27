<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    // ═══════════════════════════════════════════
    //  RAZORPAY
    // ═══════════════════════════════════════════

    /**
     * Create a Razorpay order for checkout.
     */
    public static function createRazorpayOrder(int $amountPaise, string $currency = 'INR', array $notes = []): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $keyId = $settings->razorpay_key_id;
        $keySecret = $settings->razorpay_key_secret;

        if (!$keyId || !$keySecret) {
            Log::error('Razorpay credentials not configured');
            return null;
        }

        try {
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $amountPaise,
                    'currency' => $currency,
                    'notes' => $notes,
                    'payment_capture' => 1, // Auto-capture
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Razorpay order creation failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Razorpay order creation exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify Razorpay payment signature.
     */
    public static function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $settings = DB::table('tbl_settings')->first();
        $keySecret = $settings->razorpay_key_secret;

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Fetch Razorpay payment details.
     */
    public static function fetchRazorpayPayment(string $paymentId): ?array
    {
        $settings = DB::table('tbl_settings')->first();

        try {
            $response = Http::withBasicAuth($settings->razorpay_key_id, $settings->razorpay_key_secret)
                ->get("https://api.razorpay.com/v1/payments/{$paymentId}");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Razorpay fetch payment error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initiate Razorpay refund.
     */
    public static function initiateRazorpayRefund(string $paymentId, int $amountPaise, array $notes = []): ?array
    {
        $settings = DB::table('tbl_settings')->first();

        try {
            $body = ['amount' => $amountPaise, 'notes' => $notes];
            $response = Http::withBasicAuth($settings->razorpay_key_id, $settings->razorpay_key_secret)
                ->post("https://api.razorpay.com/v1/payments/{$paymentId}/refund", $body);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Razorpay refund failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Razorpay refund exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create Razorpay Route transfer (split payment to seller).
     */
    public static function createRazorpayTransfer(string $paymentId, int $amountPaise, string $linkedAccountId, array $notes = []): ?array
    {
        $settings = DB::table('tbl_settings')->first();

        try {
            $response = Http::withBasicAuth($settings->razorpay_key_id, $settings->razorpay_key_secret)
                ->post("https://api.razorpay.com/v1/payments/{$paymentId}/transfers", [
                    'transfers' => [[
                        'account' => $linkedAccountId,
                        'amount' => $amountPaise,
                        'currency' => 'INR',
                        'notes' => $notes,
                        'on_hold' => true,
                        'on_hold_until' => null, // Released manually after return window
                    ]],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Razorpay transfer failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Razorpay transfer exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Release Razorpay Route hold on transfer.
     */
    public static function releaseRazorpayTransferHold(string $transferId): ?array
    {
        $settings = DB::table('tbl_settings')->first();

        try {
            $response = Http::withBasicAuth($settings->razorpay_key_id, $settings->razorpay_key_secret)
                ->patch("https://api.razorpay.com/v1/transfers/{$transferId}", [
                    'on_hold' => false,
                ]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Razorpay release hold exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ═══════════════════════════════════════════
    //  CASHFREE
    // ═══════════════════════════════════════════

    /**
     * Create a Cashfree order.
     */
    public static function createCashfreeOrder(int $amountPaise, string $orderId, string $customerName, string $customerPhone, string $customerEmail = ''): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $appId = $settings->cashfree_app_id;
        $secretKey = $settings->cashfree_secret_key;

        if (!$appId || !$secretKey) {
            Log::error('Cashfree credentials not configured');
            return null;
        }

        $amountRupees = round($amountPaise / 100, 2);
        $baseUrl = app()->environment('production')
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';

        try {
            $response = Http::withHeaders([
                'x-client-id' => $appId,
                'x-client-secret' => $secretKey,
                'x-api-version' => '2023-08-01',
            ])->post("{$baseUrl}/orders", [
                'order_id' => $orderId,
                'order_amount' => $amountRupees,
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'cust_' . $orderId,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'customer_email' => $customerEmail ?: 'customer@kick.mybd.in',
                ],
                'order_meta' => [
                    'return_url' => url('/api/payment/cashfree/callback?order_id={order_id}'),
                ],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cashfree order creation failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Cashfree order creation exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify Cashfree payment status.
     */
    public static function verifyCashfreePayment(string $orderId): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $baseUrl = app()->environment('production')
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';

        try {
            $response = Http::withHeaders([
                'x-client-id' => $settings->cashfree_app_id,
                'x-client-secret' => $settings->cashfree_secret_key,
                'x-api-version' => '2023-08-01',
            ])->get("{$baseUrl}/orders/{$orderId}/payments");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Cashfree verify error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initiate Cashfree refund.
     */
    public static function initiateCashfreeRefund(string $orderId, string $refundId, int $amountPaise, string $reason = ''): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $baseUrl = app()->environment('production')
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';

        try {
            $response = Http::withHeaders([
                'x-client-id' => $settings->cashfree_app_id,
                'x-client-secret' => $settings->cashfree_secret_key,
                'x-api-version' => '2023-08-01',
            ])->post("{$baseUrl}/orders/{$orderId}/refunds", [
                'refund_id' => $refundId,
                'refund_amount' => round($amountPaise / 100, 2),
                'refund_note' => $reason,
            ]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Cashfree refund exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ═══════════════════════════════════════════
    //  PHONEPE
    // ═══════════════════════════════════════════

    /**
     * Initiate PhonePe payment.
     */
    public static function createPhonePePayment(int $amountPaise, string $merchantTransactionId, string $callbackUrl, string $redirectUrl, string $mobileNumber = ''): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $merchantId = $settings->phonepe_merchant_id;
        $saltKey = $settings->phonepe_salt_key;
        $saltIndex = $settings->phonepe_salt_index ?? 1;

        if (!$merchantId || !$saltKey) {
            Log::error('PhonePe credentials not configured');
            return null;
        }

        $baseUrl = app()->environment('production')
            ? 'https://api.phonepe.com/apis/hermes'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        $payload = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => 'MUID_' . $merchantTransactionId,
            'amount' => $amountPaise,
            'redirectUrl' => $redirectUrl,
            'redirectMode' => 'POST',
            'callbackUrl' => $callbackUrl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        if ($mobileNumber) {
            $payload['mobileNumber'] = $mobileNumber;
        }

        $base64Payload = base64_encode(json_encode($payload));
        $apiEndpoint = '/pg/v1/pay';
        $checksum = hash('sha256', $base64Payload . $apiEndpoint . $saltKey) . '###' . $saltIndex;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
            ])->post("{$baseUrl}{$apiEndpoint}", [
                'request' => $base64Payload,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PhonePe payment init failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('PhonePe payment exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check PhonePe payment status.
     */
    public static function checkPhonePeStatus(string $merchantTransactionId): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $merchantId = $settings->phonepe_merchant_id;
        $saltKey = $settings->phonepe_salt_key;
        $saltIndex = $settings->phonepe_salt_index ?? 1;

        $baseUrl = app()->environment('production')
            ? 'https://api.phonepe.com/apis/hermes'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        $apiEndpoint = "/pg/v1/status/{$merchantId}/{$merchantTransactionId}";
        $checksum = hash('sha256', $apiEndpoint . $saltKey) . '###' . $saltIndex;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => $merchantId,
            ])->get("{$baseUrl}{$apiEndpoint}");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('PhonePe status check exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initiate PhonePe refund.
     */
    public static function initiatePhonePeRefund(string $merchantTransactionId, string $originalTransactionId, int $amountPaise): ?array
    {
        $settings = DB::table('tbl_settings')->first();
        $merchantId = $settings->phonepe_merchant_id;
        $saltKey = $settings->phonepe_salt_key;
        $saltIndex = $settings->phonepe_salt_index ?? 1;

        $baseUrl = app()->environment('production')
            ? 'https://api.phonepe.com/apis/hermes'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        $payload = [
            'merchantId' => $merchantId,
            'merchantUserId' => 'MUID_' . $originalTransactionId,
            'merchantTransactionId' => $merchantTransactionId,
            'originalTransactionId' => $originalTransactionId,
            'amount' => $amountPaise,
            'callbackUrl' => url('/api/payment/phonepe/refund-callback'),
        ];

        $base64Payload = base64_encode(json_encode($payload));
        $apiEndpoint = '/pg/v1/refund';
        $checksum = hash('sha256', $base64Payload . $apiEndpoint . $saltKey) . '###' . $saltIndex;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
            ])->post("{$baseUrl}{$apiEndpoint}", [
                'request' => $base64Payload,
            ]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('PhonePe refund exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ═══════════════════════════════════════════
    //  UNIFIED INTERFACE
    // ═══════════════════════════════════════════

    /**
     * Create a payment order using the specified gateway.
     * Returns: ['gateway_order_id' => ..., 'gateway_data' => ..., 'transaction' => PaymentTransaction]
     */
    public static function createPaymentOrder(
        string $gateway,
        int $userId,
        int $orderId,
        int $amountPaise,
        string $customerName = '',
        string $customerPhone = '',
        string $customerEmail = ''
    ): ?array {
        $internalOrderId = 'KICK_' . $orderId . '_' . time();

        // Create the transaction record
        $transaction = PaymentTransaction::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'gateway' => $gateway,
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'status' => PaymentTransaction::STATUS_CREATED,
        ]);

        $result = null;

        switch ($gateway) {
            case PaymentTransaction::GATEWAY_RAZORPAY:
                $rzpOrder = self::createRazorpayOrder($amountPaise, 'INR', ['order_id' => $orderId]);
                if ($rzpOrder) {
                    $transaction->update(['gateway_order_id' => $rzpOrder['id']]);
                    $settings = DB::table('tbl_settings')->first();
                    $result = [
                        'gateway_order_id' => $rzpOrder['id'],
                        'razorpay_key' => $settings->razorpay_key_id,
                        'amount' => $amountPaise,
                        'currency' => 'INR',
                        'transaction_id' => $transaction->id,
                    ];
                }
                break;

            case PaymentTransaction::GATEWAY_CASHFREE:
                $cfOrder = self::createCashfreeOrder($amountPaise, $internalOrderId, $customerName, $customerPhone, $customerEmail);
                if ($cfOrder) {
                    $transaction->update([
                        'gateway_order_id' => $cfOrder['cf_order_id'] ?? $internalOrderId,
                        'metadata' => $cfOrder,
                    ]);
                    $result = [
                        'gateway_order_id' => $cfOrder['cf_order_id'] ?? $internalOrderId,
                        'payment_session_id' => $cfOrder['payment_session_id'] ?? null,
                        'transaction_id' => $transaction->id,
                    ];
                }
                break;

            case PaymentTransaction::GATEWAY_PHONEPE:
                $callbackUrl = url('/api/payment/phonepe/callback');
                $redirectUrl = url('/api/payment/phonepe/redirect');
                $ppResult = self::createPhonePePayment($amountPaise, $internalOrderId, $callbackUrl, $redirectUrl, $customerPhone);
                if ($ppResult && ($ppResult['success'] ?? false)) {
                    $instrumentResponse = $ppResult['data']['instrumentResponse'] ?? [];
                    $transaction->update([
                        'gateway_order_id' => $internalOrderId,
                        'metadata' => $ppResult,
                    ]);
                    $result = [
                        'gateway_order_id' => $internalOrderId,
                        'redirect_url' => $instrumentResponse['redirectInfo']['url'] ?? null,
                        'transaction_id' => $transaction->id,
                    ];
                }
                break;
        }

        if (!$result) {
            $transaction->update(['status' => PaymentTransaction::STATUS_FAILED, 'failure_reason' => 'Gateway order creation failed']);
            return null;
        }

        $result['transaction'] = $transaction;
        return $result;
    }

    /**
     * Verify and confirm a payment from any gateway.
     * Returns updated PaymentTransaction or null on failure.
     */
    public static function verifyPayment(int $transactionId, array $gatewayResponse): ?PaymentTransaction
    {
        $transaction = PaymentTransaction::find($transactionId);
        if (!$transaction || $transaction->status === PaymentTransaction::STATUS_CAPTURED) {
            return $transaction;
        }

        $verified = false;
        $paymentId = null;
        $paymentMethod = null;

        switch ($transaction->gateway) {
            case PaymentTransaction::GATEWAY_RAZORPAY:
                $rzpOrderId = $gatewayResponse['razorpay_order_id'] ?? '';
                $rzpPaymentId = $gatewayResponse['razorpay_payment_id'] ?? '';
                $rzpSignature = $gatewayResponse['razorpay_signature'] ?? '';

                if (self::verifyRazorpaySignature($rzpOrderId, $rzpPaymentId, $rzpSignature)) {
                    $paymentDetails = self::fetchRazorpayPayment($rzpPaymentId);
                    $verified = $paymentDetails && ($paymentDetails['status'] === 'captured');
                    $paymentId = $rzpPaymentId;
                    $paymentMethod = $paymentDetails['method'] ?? null;
                }
                break;

            case PaymentTransaction::GATEWAY_CASHFREE:
                $payments = self::verifyCashfreePayment($transaction->gateway_order_id);
                if ($payments && is_array($payments)) {
                    foreach ($payments as $payment) {
                        if (($payment['payment_status'] ?? '') === 'SUCCESS') {
                            $verified = true;
                            $paymentId = $payment['cf_payment_id'] ?? null;
                            $paymentMethod = $payment['payment_group'] ?? null;
                            break;
                        }
                    }
                }
                break;

            case PaymentTransaction::GATEWAY_PHONEPE:
                $statusResult = self::checkPhonePeStatus($transaction->gateway_order_id);
                if ($statusResult && ($statusResult['code'] ?? '') === 'PAYMENT_SUCCESS') {
                    $verified = true;
                    $paymentId = $statusResult['data']['transactionId'] ?? null;
                    $paymentMethod = $statusResult['data']['paymentInstrument']['type'] ?? 'upi';
                }
                break;
        }

        if ($verified) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_CAPTURED,
                'gateway_payment_id' => $paymentId,
                'payment_method' => self::normalizePaymentMethod($paymentMethod),
                'payment_details' => $gatewayResponse,
            ]);
        } else {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => 'Payment verification failed',
                'payment_details' => $gatewayResponse,
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Unified refund initiation.
     */
    public static function initiateRefund(PaymentTransaction $transaction, int $amountPaise, string $reason = ''): ?array
    {
        $result = null;
        $refundId = 'REF_' . $transaction->id . '_' . time();

        switch ($transaction->gateway) {
            case PaymentTransaction::GATEWAY_RAZORPAY:
                $result = self::initiateRazorpayRefund($transaction->gateway_payment_id, $amountPaise, ['reason' => $reason]);
                break;

            case PaymentTransaction::GATEWAY_CASHFREE:
                $result = self::initiateCashfreeRefund($transaction->gateway_order_id, $refundId, $amountPaise, $reason);
                break;

            case PaymentTransaction::GATEWAY_PHONEPE:
                $result = self::initiatePhonePeRefund($refundId, $transaction->gateway_order_id, $amountPaise);
                break;
        }

        if ($result) {
            $newRefundAmount = ($transaction->refund_amount_paise ?? 0) + $amountPaise;
            $newStatus = $newRefundAmount >= $transaction->amount_paise
                ? PaymentTransaction::STATUS_REFUNDED
                : PaymentTransaction::STATUS_PARTIALLY_REFUNDED;

            $transaction->update([
                'refund_amount_paise' => $newRefundAmount,
                'refund_id' => $refundId,
                'refunded_at' => now(),
                'status' => $newStatus,
            ]);
        }

        return $result;
    }

    /**
     * Normalize payment method from various gateways to our constants.
     */
    private static function normalizePaymentMethod(?string $method): string
    {
        if (!$method) return PaymentTransaction::METHOD_UPI;

        $method = strtolower($method);
        return match (true) {
            str_contains($method, 'upi') => PaymentTransaction::METHOD_UPI,
            str_contains($method, 'card'), str_contains($method, 'credit'), str_contains($method, 'debit') => PaymentTransaction::METHOD_CARD,
            str_contains($method, 'netbanking'), str_contains($method, 'nb') => PaymentTransaction::METHOD_NETBANKING,
            str_contains($method, 'wallet') => PaymentTransaction::METHOD_WALLET,
            str_contains($method, 'emi') => PaymentTransaction::METHOD_EMI,
            str_contains($method, 'cod') => PaymentTransaction::METHOD_COD,
            default => PaymentTransaction::METHOD_UPI,
        };
    }

    /**
     * Get available payment gateways from settings.
     */
    public static function getAvailableGateways(): array
    {
        $settings = DB::table('tbl_settings')->first();
        $gateways = [];

        if ($settings->razorpay_enabled ?? false) {
            $gateways[] = ['id' => 'razorpay', 'name' => 'Razorpay', 'supports_upi' => true, 'supports_cards' => true];
        }
        if ($settings->cashfree_enabled ?? false) {
            $gateways[] = ['id' => 'cashfree', 'name' => 'Cashfree', 'supports_upi' => true, 'supports_cards' => true];
        }
        if ($settings->phonepe_enabled ?? false) {
            $gateways[] = ['id' => 'phonepe', 'name' => 'PhonePe', 'supports_upi' => true, 'supports_cards' => false];
        }

        return $gateways;
    }
}
