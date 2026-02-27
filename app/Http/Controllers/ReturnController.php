<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\ProductOrder;
use App\Models\ProductReturn;
use App\Services\PaymentGatewayService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReturnController extends Controller
{
    /**
     * Buyer requests a return for an order item.
     */
    public function requestReturn(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:tbl_product_orders,id',
            'order_item_id' => 'nullable|integer|exists:tbl_order_items,id',
            'reason' => 'required|in:defective,wrong_item,not_as_described,size_issue,change_of_mind,damaged_in_transit,other',
            'description' => 'required|string|max:2000',
            'return_type' => 'nullable|in:refund,replacement,exchange',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $order = ProductOrder::where('id', $request->order_id)
            ->where('buyer_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.']);
        }

        if ($order->status !== ProductOrder::STATUS_DELIVERED) {
            return response()->json(['status' => false, 'message' => 'Returns can only be requested for delivered orders.']);
        }

        // Check return window
        if (!$order->isReturnWindowOpen()) {
            return response()->json(['status' => false, 'message' => 'Return window has expired.']);
        }

        // Check for existing return
        $existingReturn = ProductReturn::where('order_id', $order->id)
            ->whereNotIn('status', [
                ProductReturn::STATUS_REJECTED,
                ProductReturn::STATUS_REFUND_COMPLETED,
            ])
            ->first();

        if ($existingReturn) {
            return response()->json(['status' => false, 'message' => 'A return request already exists for this order.']);
        }

        // Determine product from order item or order
        $productId = $order->product_id;
        if ($request->order_item_id) {
            $item = OrderItem::where('id', $request->order_item_id)
                ->where('order_id', $order->id)
                ->first();
            if ($item) {
                $productId = $item->product_id;
            }
        }

        // Handle photo uploads
        $photos = [];
        if ($request->hasFile('photos')) {
            $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
            foreach ($files as $file) {
                $photos[] = GlobalFunction::saveFileAndGivePath($file);
            }
        }

        $return = ProductReturn::create([
            'order_id' => $order->id,
            'order_item_id' => $request->order_item_id,
            'buyer_id' => $user->id,
            'seller_id' => $order->seller_id,
            'product_id' => $productId,
            'reason' => $request->reason,
            'description' => $request->description,
            'photos' => !empty($photos) ? $photos : null,
            'return_type' => $request->return_type ?? ProductReturn::TYPE_REFUND,
            'status' => ProductReturn::STATUS_REQUESTED,
        ]);

        // Update order status
        $order->update(['status' => ProductOrder::STATUS_RETURN_REQUESTED]);
        OrderStatusHistory::record($order->id, ProductOrder::STATUS_RETURN_REQUESTED, 'Return Requested', 'Buyer requested return: ' . $request->reason);

        return response()->json([
            'status' => true,
            'message' => 'Return request submitted.',
            'data' => $return,
        ]);
    }

    /**
     * Seller responds to a return request (approve/reject).
     */
    public function respondToReturn(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'return_id' => 'required|integer|exists:tbl_returns,id',
            'action' => 'required|in:approve,reject',
            'seller_response' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $return = ProductReturn::where('id', $request->return_id)
            ->where('seller_id', $user->id)
            ->where('status', ProductReturn::STATUS_REQUESTED)
            ->first();

        if (!$return) {
            return response()->json(['status' => false, 'message' => 'Return request not found or already processed.']);
        }

        if ($request->action === 'approve') {
            $return->update([
                'status' => ProductReturn::STATUS_APPROVED,
                'seller_response' => $request->seller_response,
                'approved_at' => now(),
            ]);

            $order = ProductOrder::find($return->order_id);
            if ($order) {
                $order->update(['status' => ProductOrder::STATUS_RETURN_IN_PROGRESS]);
                OrderStatusHistory::record($order->id, ProductOrder::STATUS_RETURN_IN_PROGRESS, 'Return Approved', 'Seller approved return request');
            }

            // Try to create reverse shipment via Shiprocket
            $this->scheduleReturnPickup($return);

            $message = 'Return approved. Pickup will be scheduled.';
        } else {
            $return->update([
                'status' => ProductReturn::STATUS_REJECTED,
                'seller_response' => $request->seller_response,
            ]);
            $message = 'Return request rejected.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $return->fresh(),
        ]);
    }

    /**
     * Fetch returns for buyer or seller.
     */
    public function fetchReturns(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        $role = $request->input('role', 'buyer');
        $limit = min($request->input('limit', 20), 50);

        $query = ProductReturn::with([
            'product:id,name,images',
            'buyer:id,username,fullname,profile_photo',
            'seller:id,username,fullname,profile_photo',
        ]);

        if ($role === 'seller') {
            $query->where('seller_id', $user->id);
        } else {
            $query->where('buyer_id', $user->id);
        }

        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $returns = $query->orderByDesc('created_at')->limit($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched.',
            'data' => $returns,
        ]);
    }

    /**
     * Seller confirms item received and inspection.
     */
    public function inspectReturn(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'return_id' => 'required|integer|exists:tbl_returns,id',
            'inspection_result' => 'required|in:passed,failed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $return = ProductReturn::where('id', $request->return_id)
            ->where('seller_id', $user->id)
            ->where('status', ProductReturn::STATUS_RECEIVED_BY_SELLER)
            ->first();

        if (!$return) {
            return response()->json(['status' => false, 'message' => 'Return not found or not ready for inspection.']);
        }

        // Handle inspection photos
        $inspectionPhotos = [];
        if ($request->hasFile('inspection_photos')) {
            $files = is_array($request->file('inspection_photos')) ? $request->file('inspection_photos') : [$request->file('inspection_photos')];
            foreach ($files as $file) {
                $inspectionPhotos[] = GlobalFunction::saveFileAndGivePath($file);
            }
        }

        if ($request->inspection_result === 'passed') {
            $order = ProductOrder::find($return->order_id);
            $refundAmount = $return->return_type === ProductReturn::TYPE_REFUND
                ? ($order->total_amount_paise ?? 0)
                : 0;

            $return->update([
                'status' => ProductReturn::STATUS_INSPECTION_PASSED,
                'seller_inspection_photos' => !empty($inspectionPhotos) ? $inspectionPhotos : null,
                'admin_notes' => $request->admin_notes,
                'refund_amount_paise' => $refundAmount,
            ]);

            // Auto-initiate refund if type is refund
            if ($return->return_type === ProductReturn::TYPE_REFUND && $refundAmount > 0) {
                $this->initiateRefund($return);
            }

            $message = 'Inspection passed. Refund will be processed.';
        } else {
            $return->update([
                'status' => ProductReturn::STATUS_INSPECTION_FAILED,
                'seller_inspection_photos' => !empty($inspectionPhotos) ? $inspectionPhotos : null,
                'admin_notes' => $request->admin_notes,
            ]);
            $message = 'Inspection failed. Return rejected after inspection.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $return->fresh(),
        ]);
    }

    /**
     * Admin: update return status manually.
     */
    public function updateReturnStatus_Admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'return_id' => 'required|integer|exists:tbl_returns,id',
            'status' => 'required|integer|min:0|max:10',
            'admin_notes' => 'nullable|string|max:2000',
            'refund_amount_paise' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $return = ProductReturn::findOrFail($request->return_id);
        $updateData = [
            'status' => $request->status,
            'admin_notes' => $request->admin_notes ?? $return->admin_notes,
        ];

        if ($request->refund_amount_paise !== null) {
            $updateData['refund_amount_paise'] = $request->refund_amount_paise;
        }

        // Set timestamps based on status
        switch ($request->status) {
            case ProductReturn::STATUS_APPROVED:
                $updateData['approved_at'] = now();
                break;
            case ProductReturn::STATUS_PICKUP_SCHEDULED:
                $updateData['pickup_scheduled_at'] = now();
                break;
            case ProductReturn::STATUS_RECEIVED_BY_SELLER:
                $updateData['received_at'] = now();
                break;
            case ProductReturn::STATUS_REFUND_INITIATED:
                $updateData['refund_initiated_at'] = now();
                break;
            case ProductReturn::STATUS_REFUND_COMPLETED:
                $updateData['refund_completed_at'] = now();
                $order = ProductOrder::find($return->order_id);
                if ($order) {
                    $order->update(['status' => ProductOrder::STATUS_RETURN_COMPLETED]);
                }
                break;
        }

        $return->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'Return status updated.',
            'data' => $return->fresh(),
        ]);
    }

    /**
     * Schedule return pickup via shipping aggregator.
     */
    private function scheduleReturnPickup(ProductReturn $return): void
    {
        $order = ProductOrder::find($return->order_id);
        if (!$order || !$order->shipping_method || $order->shipping_method === 'self') {
            return;
        }

        try {
            $result = ShippingService::createReturnOrder([
                'order_id' => $order->shiprocket_order_id,
                'order_date' => now()->format('Y-m-d'),
                'pickup_customer_name' => '', // Would come from buyer address
                'pickup_address' => $order->shipping_address,
                'pickup_city' => '',
                'pickup_state' => '',
                'pickup_pincode' => '',
                'pickup_phone' => '',
            ]);

            if ($result) {
                $return->update([
                    'status' => ProductReturn::STATUS_PICKUP_SCHEDULED,
                    'shiprocket_return_order_id' => $result['order_id'] ?? null,
                    'return_awb' => $result['awb_code'] ?? null,
                    'return_courier' => $result['courier_name'] ?? null,
                    'pickup_scheduled_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Return pickup scheduling failed', ['return_id' => $return->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Initiate refund through payment gateway.
     */
    private function initiateRefund(ProductReturn $return): void
    {
        $order = ProductOrder::find($return->order_id);
        if (!$order || !$order->payment_transaction_id) {
            return;
        }

        $transaction = PaymentTransaction::find($order->payment_transaction_id);
        if (!$transaction || !$transaction->gateway_payment_id) {
            return;
        }

        try {
            $refundResult = PaymentGatewayService::initiateRefund(
                $transaction,
                $return->refund_amount_paise,
                "Return #{$return->id} - {$return->reason}"
            );

            if ($refundResult) {
                $return->update([
                    'status' => ProductReturn::STATUS_REFUND_INITIATED,
                    'refund_method' => $transaction->gateway,
                    'refund_gateway_id' => $transaction->refund_id,
                    'refund_initiated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Refund initiation failed', ['return_id' => $return->id, 'error' => $e->getMessage()]);
        }
    }
}
