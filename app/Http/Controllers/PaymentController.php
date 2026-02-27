<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\GlobalFunction;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ShippingAddress;
use App\Services\PaymentGatewayService;
use App\Services\PayoutService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Initiate checkout — creates orders and payment order.
     * Flow: Cart → createOrders → paymentGateway → verifyPayment → processShipping
     */
    public function initiateCheckout(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer|exists:tbl_shipping_addresses,id',
            'payment_method' => 'required|in:prepaid,cod',
            'gateway' => 'required_if:payment_method,prepaid|in:razorpay,cashfree,phonepe',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $address = ShippingAddress::where('user_id', $user->id)
            ->where('id', $request->address_id)
            ->first();

        if (!$address) {
            return response()->json(['status' => false, 'message' => 'Invalid shipping address.']);
        }

        // Fetch cart items with products and variants
        $cartItems = CartItem::where('user_id', $user->id)
            ->with(['product.category', 'variant'])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Cart is empty.']);
        }

        // Validate all items
        $errors = [];
        $totalAmountPaise = 0;
        $totalShippingPaise = 0;
        $totalGstPaise = 0;
        $orderItemsData = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product || !$product->is_active || $product->status !== Product::STATUS_APPROVED) {
                $errors[] = "'{$product->name}' is no longer available.";
                continue;
            }

            if (!$product->hasStock($cartItem->quantity, $cartItem->variant_id)) {
                $errors[] = "'{$product->name}' has insufficient stock.";
                continue;
            }

            // COD check
            if ($request->payment_method === 'cod' && !$product->cod_available) {
                $errors[] = "'{$product->name}' does not support COD.";
                continue;
            }

            $pricePaise = $product->getEffectivePricePaise($cartItem->variant_id);
            $lineTotal = $pricePaise * $cartItem->quantity;
            $shippingCharge = $product->shipping_charge_paise ?? 0;
            $gstRate = $product->gst_rate ?? 0;
            $gstAmount = (int) round($lineTotal * ($gstRate / 100));

            $variantLabel = null;
            if ($cartItem->variant_id && $cartItem->variant) {
                $variantLabel = $cartItem->variant->getLabel();
            }

            $totalAmountPaise += $lineTotal;
            $totalShippingPaise += $shippingCharge;
            $totalGstPaise += $gstAmount;

            $orderItemsData[] = [
                'product' => $product,
                'variant_id' => $cartItem->variant_id,
                'variant_label' => $variantLabel,
                'quantity' => $cartItem->quantity,
                'price_paise' => $pricePaise,
                'line_total' => $lineTotal,
                'shipping_charge' => $shippingCharge,
                'gst_amount' => $gstAmount,
            ];
        }

        if (!empty($errors)) {
            return response()->json(['status' => false, 'message' => implode(' ', $errors)]);
        }

        $grandTotalPaise = $totalAmountPaise + $totalShippingPaise;

        // COD limit check
        if ($request->payment_method === 'cod') {
            $settings = DB::table('tbl_settings')->first();
            $codMax = $settings->cod_max_amount_paise ?? 500000; // ₹5000
            if ($grandTotalPaise > $codMax) {
                return response()->json([
                    'status' => false,
                    'message' => 'COD is not available for orders above ₹' . ($codMax / 100) . '.',
                ]);
            }
        }

        // Create orders grouped by seller
        $sellerGroups = collect($orderItemsData)->groupBy(fn($item) => $item['product']->seller_id);

        return DB::transaction(function () use ($user, $sellerGroups, $grandTotalPaise, $totalShippingPaise, $totalGstPaise, $address, $request) {
            $orderIds = [];
            $allOrders = [];

            foreach ($sellerGroups as $sellerId => $items) {
                $sellerTotal = collect($items)->sum('line_total');
                $sellerShipping = collect($items)->sum('shipping_charge');
                $sellerGst = collect($items)->sum('gst_amount');
                $sellerOrderTotal = $sellerTotal + $sellerShipping;

                // Get commission rate from first product's category
                $commissionRate = $items->first()['product']->getCommissionRate();

                // Calculate return window from max of all products
                $maxReturnDays = $items->max(fn($i) => $i['product']->getReturnWindowDays());

                $order = ProductOrder::create([
                    'product_id' => $items->first()['product']->id,
                    'buyer_id' => $user->id,
                    'seller_id' => $sellerId,
                    'quantity' => $items->sum('quantity'),
                    'total_coins' => 0,
                    'total_amount_paise' => $sellerOrderTotal,
                    'shipping_charge_paise' => $sellerShipping,
                    'gst_amount_paise' => $sellerGst,
                    'platform_commission_rate' => $commissionRate,
                    'payment_method' => $request->payment_method,
                    'shipping_method' => $items->first()['product']->shipping_type === 'self' ? 'self' : 'shiprocket',
                    'status' => ProductOrder::STATUS_PENDING,
                    'shipping_address' => json_encode($address->toArray()),
                    'shipping_address_id' => $address->id,
                    'invoice_number' => ProductOrder::generateInvoiceNumber(),
                ]);

                // Calculate financials
                $financials = PayoutService::calculateOrderFinancials($order, $commissionRate);
                $order->update($financials);

                // Create order items
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'variant_id' => $item['variant_id'],
                        'quantity' => $item['quantity'],
                        'price_paise' => $item['price_paise'],
                        'price_coins' => 0,
                        'variant_label' => $item['variant_label'],
                    ]);

                    // Decrement stock
                    if ($item['product']->stock != -1) {
                        $item['product']->decrement('stock', $item['quantity']);
                    }
                    if ($item['variant_id']) {
                        \App\Models\ProductVariant::where('id', $item['variant_id'])
                            ->where('stock', '!=', -1)
                            ->decrement('stock', $item['quantity']);
                    }
                    $item['product']->increment('sold_count', $item['quantity']);
                }

                OrderStatusHistory::record($order->id, ProductOrder::STATUS_PENDING, 'Order Created', 'Order placed by buyer');

                $orderIds[] = $order->id;
                $allOrders[] = $order;
            }

            // For prepaid: create payment order
            if ($request->payment_method === 'prepaid') {
                $paymentResult = PaymentGatewayService::createPaymentOrder(
                    $request->gateway,
                    $user->id,
                    $orderIds[0], // Primary order for reference
                    $grandTotalPaise,
                    $user->fullname ?? $user->username,
                    $user->phone ?? '',
                    $user->email ?? ''
                );

                if (!$paymentResult) {
                    throw new \Exception('Payment gateway error. Please try again.');
                }

                // Link payment transaction to all orders
                foreach ($allOrders as $order) {
                    $order->update(['payment_transaction_id' => $paymentResult['transaction']->id]);
                }

                // Clear cart
                CartItem::where('user_id', $user->id)->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Payment initiated.',
                    'data' => [
                        'order_ids' => $orderIds,
                        'payment' => [
                            'gateway' => $request->gateway,
                            'transaction_id' => $paymentResult['transaction_id'],
                            'gateway_order_id' => $paymentResult['gateway_order_id'],
                            'razorpay_key' => $paymentResult['razorpay_key'] ?? null,
                            'payment_session_id' => $paymentResult['payment_session_id'] ?? null,
                            'redirect_url' => $paymentResult['redirect_url'] ?? null,
                            'amount_paise' => $grandTotalPaise,
                        ],
                    ],
                ]);
            }

            // For COD: orders are confirmed immediately
            foreach ($allOrders as $order) {
                $order->update(['status' => ProductOrder::STATUS_CONFIRMED]);
                OrderStatusHistory::record($order->id, ProductOrder::STATUS_CONFIRMED, 'Order Confirmed', 'COD order confirmed');
                PayoutService::recordOrderTaxes($order);
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully (COD).',
                'data' => [
                    'order_ids' => $orderIds,
                    'payment_method' => 'cod',
                    'total_amount_paise' => $grandTotalPaise,
                ],
            ]);
        });
    }

    /**
     * Verify payment after gateway callback.
     * Called from Flutter after Razorpay/Cashfree/PhonePe SDK callback.
     */
    public function verifyPayment(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer|exists:tbl_payment_transactions,id',
            'gateway_response' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $transaction = PaymentTransaction::where('id', $request->transaction_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json(['status' => false, 'message' => 'Transaction not found.']);
        }

        if ($transaction->status === PaymentTransaction::STATUS_CAPTURED) {
            return response()->json(['status' => true, 'message' => 'Payment already verified.']);
        }

        $verifiedTransaction = PaymentGatewayService::verifyPayment($transaction->id, $request->gateway_response);

        if (!$verifiedTransaction || $verifiedTransaction->status !== PaymentTransaction::STATUS_CAPTURED) {
            // Rollback: restore stock
            $this->rollbackOrderStock($transaction);

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed. Your order has been cancelled.',
            ]);
        }

        // Confirm all orders linked to this transaction
        $orders = ProductOrder::where('payment_transaction_id', $transaction->id)->get();
        foreach ($orders as $order) {
            $order->update(['status' => ProductOrder::STATUS_CONFIRMED]);
            OrderStatusHistory::record($order->id, ProductOrder::STATUS_CONFIRMED, 'Payment Confirmed', 'Payment verified via ' . $transaction->gateway);
            PayoutService::recordOrderTaxes($order);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment verified. Orders confirmed.',
            'data' => [
                'transaction_status' => $verifiedTransaction->status,
                'payment_method' => $verifiedTransaction->payment_method,
                'order_ids' => $orders->pluck('id'),
            ],
        ]);
    }

    /**
     * Get checkout summary (before initiating payment).
     */
    public function getCheckoutSummary(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $cartItems = CartItem::where('user_id', $user->id)
            ->with(['product.category', 'variant'])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Cart is empty.']);
        }

        $subtotalPaise = 0;
        $totalShippingPaise = 0;
        $totalGstPaise = 0;
        $codAvailable = true;
        $items = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product || !$product->is_active) continue;

            $pricePaise = $product->getEffectivePricePaise($cartItem->variant_id);
            $lineTotal = $pricePaise * $cartItem->quantity;
            $shipping = $product->shipping_charge_paise ?? 0;
            $gst = (int) round($lineTotal * (($product->gst_rate ?? 0) / 100));

            $subtotalPaise += $lineTotal;
            $totalShippingPaise += $shipping;
            $totalGstPaise += $gst;

            if (!$product->cod_available) {
                $codAvailable = false;
            }

            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => ($product->images && count($product->images) > 0) ? GlobalFunction::generateFileUrl($product->images[0]) : null,
                'variant_id' => $cartItem->variant_id,
                'variant_label' => $cartItem->variant ? $cartItem->variant->getLabel() : null,
                'quantity' => $cartItem->quantity,
                'price_paise' => $pricePaise,
                'line_total_paise' => $lineTotal,
                'shipping_paise' => $shipping,
                'gst_paise' => $gst,
            ];
        }

        $grandTotal = $subtotalPaise + $totalShippingPaise;

        // Check COD limit
        $settings = DB::table('tbl_settings')->first();
        $codMax = $settings->cod_max_amount_paise ?? 500000;
        if ($grandTotal > $codMax) {
            $codAvailable = false;
        }
        if (!($settings->cod_enabled ?? true)) {
            $codAvailable = false;
        }

        // Available payment gateways
        $gateways = PaymentGatewayService::getAvailableGateways();

        // Saved addresses
        $addresses = ShippingAddress::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Checkout summary.',
            'data' => [
                'items' => $items,
                'subtotal_paise' => $subtotalPaise,
                'shipping_paise' => $totalShippingPaise,
                'gst_paise' => $totalGstPaise,
                'grand_total_paise' => $grandTotal,
                'subtotal_rupees' => round($subtotalPaise / 100, 2),
                'shipping_rupees' => round($totalShippingPaise / 100, 2),
                'gst_rupees' => round($totalGstPaise / 100, 2),
                'grand_total_rupees' => round($grandTotal / 100, 2),
                'cod_available' => $codAvailable,
                'gateways' => $gateways,
                'addresses' => $addresses,
            ],
        ]);
    }

    /**
     * Get available payment gateways.
     */
    public function getPaymentGateways(Request $request)
    {
        $gateways = PaymentGatewayService::getAvailableGateways();

        return response()->json([
            'status' => true,
            'message' => 'Payment gateways.',
            'data' => $gateways,
        ]);
    }

    /**
     * Seller earnings dashboard.
     */
    public function sellerEarnings(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if (!$user->is_approved_seller) {
            return response()->json(['status' => false, 'message' => 'Not an approved seller.']);
        }

        $summary = PayoutService::getSellerEarningsSummary($user->id);
        $payouts = PayoutService::fetchSellerPayouts($user->id, $request->input('limit', 10));

        return response()->json([
            'status' => true,
            'message' => 'Earnings fetched.',
            'data' => [
                'summary' => $summary,
                'recent_payouts' => $payouts,
            ],
        ]);
    }

    /**
     * Fetch order tracking details.
     */
    public function trackOrder(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $order = ProductOrder::where('id', $request->order_id)
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.']);
        }

        $tracking = null;
        if ($order->awb_code && $order->shipping_method && $order->shipping_method !== 'self') {
            $tracking = ShippingService::trackOrder($order->shipping_method, $order->awb_code);
        }

        $history = OrderStatusHistory::where('order_id', $order->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Tracking details.',
            'data' => [
                'order' => $order->load('items.product:id,name,images', 'buyer:id,username,fullname', 'seller:id,username,fullname'),
                'tracking' => $tracking,
                'status_history' => $history,
            ],
        ]);
    }

    /**
     * Seller ships an order via platform aggregator.
     */
    public function shipOrder(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:tbl_product_orders,id',
            'shipping_method' => 'nullable|in:shiprocket,delhivery,self',
            'tracking_number' => 'required_if:shipping_method,self|nullable|string|max:100',
            'courier_name' => 'required_if:shipping_method,self|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $order = ProductOrder::where('id', $request->order_id)
            ->where('seller_id', $user->id)
            ->where('status', ProductOrder::STATUS_CONFIRMED)
            ->with('items.product', 'shippingAddressRecord')
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or not in confirmed status.']);
        }

        $shippingMethod = $request->shipping_method ?? $order->shipping_method ?? 'shiprocket';
        $address = $order->shippingAddressRecord ?? ($order->shipping_address ? json_decode($order->shipping_address, true) : null);

        if ($shippingMethod === 'self') {
            // Self-shipping with manual tracking
            $order->update([
                'status' => ProductOrder::STATUS_SHIPPED,
                'shipping_method' => 'self',
                'tracking_number' => $request->tracking_number,
                'courier_name' => $request->courier_name,
                'estimated_delivery_date' => now()->addDays(7),
            ]);

            OrderStatusHistory::record($order->id, ProductOrder::STATUS_SHIPPED, 'Shipped', "Self-shipped via {$request->courier_name}, AWB: {$request->tracking_number}");
        } else {
            // Ship via aggregator
            $addressData = is_object($address) ? $address->toArray() : (is_array($address) ? $address : []);

            $items = $order->items->map(fn($item) => [
                'name' => $item->product ? $item->product->name : 'Product',
                'sku' => $item->product ? ($item->product->sku ?? 'SKU_' . $item->product_id) : 'SKU',
                'units' => $item->quantity,
                'selling_price' => round(($item->price_paise ?? 0) / 100, 2),
            ])->toArray();

            // Calculate total weight
            $totalWeight = $order->items->sum(fn($item) => ($item->product->weight_grams ?? 500) * $item->quantity);

            $shippingResult = ShippingService::createShippingOrder([
                'order_id' => 'KICK_' . $order->id,
                'customer_name' => $addressData['name'] ?? 'Customer',
                'phone' => $addressData['phone'] ?? '',
                'email' => '',
                'address_line1' => $addressData['address_line1'] ?? '',
                'address_line2' => $addressData['address_line2'] ?? '',
                'city' => $addressData['city'] ?? '',
                'state' => $addressData['state'] ?? '',
                'pincode' => $addressData['zip_code'] ?? '',
                'items' => $items,
                'payment_method' => $order->payment_method,
                'total_amount_paise' => $order->total_amount_paise,
                'weight_grams' => $totalWeight,
                'length_cm' => $order->items->first()?->product?->length_cm ?? 10,
                'breadth_cm' => $order->items->first()?->product?->breadth_cm ?? 10,
                'height_cm' => $order->items->first()?->product?->height_cm ?? 10,
                'pickup_location' => $order->items->first()?->product?->pickup_location_name ?? 'Primary',
            ], $shippingMethod);

            if (!$shippingResult) {
                return response()->json(['status' => false, 'message' => 'Shipping creation failed. Please try self-shipping.']);
            }

            $order->update([
                'status' => ProductOrder::STATUS_SHIPPED,
                'shipping_method' => $shippingResult['aggregator'],
                'shiprocket_order_id' => $shippingResult['order_id'],
                'shiprocket_shipment_id' => $shippingResult['shipment_id'],
                'awb_code' => $shippingResult['awb'],
                'courier_name' => $shippingResult['courier_name'],
                'shipping_label_url' => $shippingResult['label_url'],
                'estimated_delivery_date' => now()->addDays(7),
            ]);

            OrderStatusHistory::record($order->id, ProductOrder::STATUS_SHIPPED, 'Shipped', "Shipped via {$shippingResult['courier_name']}, AWB: {$shippingResult['awb']}");
        }

        return response()->json([
            'status' => true,
            'message' => 'Order shipped.',
            'data' => $order->fresh(),
        ]);
    }

    /**
     * Mark order as delivered (for self-shipping or manual override).
     */
    public function markDelivered(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $order = ProductOrder::where('id', $request->order_id)
            ->where('seller_id', $user->id)
            ->where('status', ProductOrder::STATUS_SHIPPED)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or not shipped.']);
        }

        $product = Product::find($order->product_id);
        $returnDays = $product ? $product->getReturnWindowDays() : 7;

        $order->update([
            'status' => ProductOrder::STATUS_DELIVERED,
            'delivered_at' => now(),
            'return_window_expires_at' => now()->addDays($returnDays),
        ]);

        OrderStatusHistory::record($order->id, ProductOrder::STATUS_DELIVERED, 'Delivered', 'Order marked as delivered');

        return response()->json([
            'status' => true,
            'message' => 'Order marked as delivered.',
            'data' => $order->fresh(),
        ]);
    }

    /**
     * Cancel an order (buyer or seller).
     */
    public function cancelOrder(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $order = ProductOrder::where('id', $request->order_id)
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })
            ->whereIn('status', [ProductOrder::STATUS_PENDING, ProductOrder::STATUS_CONFIRMED])
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found or cannot be cancelled.']);
        }

        return DB::transaction(function () use ($order, $user) {
            $order->update(['status' => ProductOrder::STATUS_CANCELLED]);
            OrderStatusHistory::record($order->id, ProductOrder::STATUS_CANCELLED, 'Cancelled', "Cancelled by " . ($user->id === $order->buyer_id ? 'buyer' : 'seller'));

            // Restore stock
            $items = OrderItem::where('order_id', $order->id)->get();
            foreach ($items as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->stock != -1) {
                    $product->increment('stock', $item->quantity);
                }
                $product?->decrement('sold_count', $item->quantity);

                if ($item->variant_id) {
                    \App\Models\ProductVariant::where('id', $item->variant_id)
                        ->where('stock', '!=', -1)
                        ->increment('stock', $item->quantity);
                }
            }

            // Refund if prepaid
            if ($order->payment_transaction_id && $order->payment_method === 'prepaid') {
                $transaction = PaymentTransaction::find($order->payment_transaction_id);
                if ($transaction && $transaction->gateway_payment_id) {
                    PaymentGatewayService::initiateRefund(
                        $transaction,
                        $order->total_amount_paise,
                        "Order #{$order->id} cancelled"
                    );
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Order cancelled.',
                'data' => $order->fresh(),
            ]);
        });
    }

    /**
     * Razorpay webhook handler.
     */
    public function razorpayWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        $settings = DB::table('tbl_settings')->first();
        $expectedSignature = hash_hmac('sha256', $payload, $settings->razorpay_key_secret ?? '');

        if (!hash_equals($expectedSignature, $signature ?? '')) {
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        $eventType = $event['event'] ?? '';

        switch ($eventType) {
            case 'payment.captured':
                $paymentId = $event['payload']['payment']['entity']['id'] ?? null;
                $orderId = $event['payload']['payment']['entity']['order_id'] ?? null;
                if ($paymentId && $orderId) {
                    $txn = PaymentTransaction::where('gateway_order_id', $orderId)->first();
                    if ($txn && $txn->status !== PaymentTransaction::STATUS_CAPTURED) {
                        $txn->update([
                            'status' => PaymentTransaction::STATUS_CAPTURED,
                            'gateway_payment_id' => $paymentId,
                            'payment_method' => $event['payload']['payment']['entity']['method'] ?? null,
                        ]);
                    }
                }
                break;

            case 'refund.processed':
                $refundEntity = $event['payload']['refund']['entity'] ?? [];
                $paymentId = $refundEntity['payment_id'] ?? null;
                if ($paymentId) {
                    $txn = PaymentTransaction::where('gateway_payment_id', $paymentId)->first();
                    if ($txn) {
                        $refundAmount = ($refundEntity['amount'] ?? 0);
                        $txn->update([
                            'refund_amount_paise' => ($txn->refund_amount_paise ?? 0) + $refundAmount,
                            'refunded_at' => now(),
                        ]);
                    }
                }
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * PhonePe callback handler.
     */
    public function phonepeCallback(Request $request)
    {
        $response = $request->all();
        $merchantTransactionId = $response['merchantTransactionId'] ?? null;

        if ($merchantTransactionId) {
            $txn = PaymentTransaction::where('gateway_order_id', $merchantTransactionId)->first();
            if ($txn) {
                $statusResult = PaymentGatewayService::checkPhonePeStatus($merchantTransactionId);
                if ($statusResult && ($statusResult['code'] ?? '') === 'PAYMENT_SUCCESS') {
                    $txn->update([
                        'status' => PaymentTransaction::STATUS_CAPTURED,
                        'gateway_payment_id' => $statusResult['data']['transactionId'] ?? null,
                        'payment_method' => 'upi',
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Cashfree callback handler.
     */
    public function cashfreeCallback(Request $request)
    {
        $orderId = $request->input('order_id') ?? $request->query('order_id');

        if ($orderId) {
            $txn = PaymentTransaction::where('gateway_order_id', $orderId)->first();
            if ($txn) {
                $payments = PaymentGatewayService::verifyCashfreePayment($orderId);
                if ($payments && is_array($payments)) {
                    foreach ($payments as $payment) {
                        if (($payment['payment_status'] ?? '') === 'SUCCESS') {
                            $txn->update([
                                'status' => PaymentTransaction::STATUS_CAPTURED,
                                'gateway_payment_id' => $payment['cf_payment_id'] ?? null,
                                'payment_method' => $payment['payment_group'] ?? null,
                            ]);
                            break;
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Rollback stock if payment fails.
     */
    private function rollbackOrderStock(PaymentTransaction $transaction): void
    {
        $orders = ProductOrder::where('payment_transaction_id', $transaction->id)->get();
        foreach ($orders as $order) {
            $order->update(['status' => ProductOrder::STATUS_CANCELLED]);
            $items = OrderItem::where('order_id', $order->id)->get();
            foreach ($items as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->stock != -1) {
                    $product->increment('stock', $item->quantity);
                }
                $product?->decrement('sold_count', $item->quantity);
            }
        }
    }
}
