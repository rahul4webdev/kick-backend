<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\ProductShootRequest;
use App\Models\ShootRequestMessage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShootRequestController extends Controller
{
    /**
     * Creator submits a product shoot request.
     */
    public function createRequest(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if (!$user->is_approved_affiliate) {
            return response()->json(['status' => false, 'message' => 'Only approved affiliates can request product shoots.']);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:tbl_products,id',
            'request_type' => 'required|in:sample_delivery,onsite_visit',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            // Sample delivery fields
            'delivery_address' => 'required_if:request_type,sample_delivery|string|max:500',
            'delivery_city' => 'required_if:request_type,sample_delivery|string|max:100',
            'delivery_state' => 'required_if:request_type,sample_delivery|string|max:100',
            'delivery_pincode' => 'required_if:request_type,sample_delivery|string|max:10',
            // Onsite visit fields
            'proposed_date' => 'required_if:request_type,onsite_visit|date|after:today',
            'proposed_location' => 'required_if:request_type,onsite_visit|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $product = Product::findOrFail($request->product_id);

        // Check for existing active request
        $existing = ProductShootRequest::where('creator_id', $user->id)
            ->where('product_id', $request->product_id)
            ->whereNotIn('status', [
                ProductShootRequest::STATUS_COMPLETED,
                ProductShootRequest::STATUS_CANCELLED,
                ProductShootRequest::STATUS_SELLER_DECLINED,
            ])
            ->first();

        if ($existing) {
            return response()->json(['status' => false, 'message' => 'You already have an active request for this product.']);
        }

        $shootRequest = ProductShootRequest::create(array_merge(
            $request->only([
                'product_id', 'request_type', 'title', 'description',
                'delivery_address', 'delivery_city', 'delivery_state', 'delivery_pincode',
                'proposed_date', 'proposed_location',
            ]),
            [
                'creator_id' => $user->id,
                'seller_id' => $product->seller_id,
                'status' => ProductShootRequest::STATUS_PENDING,
            ]
        ));

        // Auto-create first message from the description
        if ($request->description) {
            ShootRequestMessage::create([
                'request_id' => $shootRequest->id,
                'sender_id' => $user->id,
                'sender_role' => ShootRequestMessage::ROLE_CREATOR,
                'message' => $request->description,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Shoot request submitted.',
            'data' => $shootRequest->load('product:id,name,images', 'seller:id,username,fullname,profile_photo'),
        ]);
    }

    /**
     * Seller responds to a shoot request (accept/decline).
     */
    public function respondToRequest(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:tbl_product_shoot_requests,id',
            'action' => 'required|in:accept,decline',
            'message' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $shootRequest = ProductShootRequest::where('id', $request->request_id)
            ->where('seller_id', $user->id)
            ->where('status', ProductShootRequest::STATUS_PENDING)
            ->first();

        if (!$shootRequest) {
            return response()->json(['status' => false, 'message' => 'Request not found or not in pending status.']);
        }

        $newStatus = $request->action === 'accept'
            ? ProductShootRequest::STATUS_SELLER_ACCEPTED
            : ProductShootRequest::STATUS_SELLER_DECLINED;

        $shootRequest->update(['status' => $newStatus]);

        if ($request->message) {
            ShootRequestMessage::create([
                'request_id' => $shootRequest->id,
                'sender_id' => $user->id,
                'sender_role' => ShootRequestMessage::ROLE_SELLER,
                'message' => $request->message,
            ]);
        }

        $msg = $request->action === 'accept' ? 'Request accepted.' : 'Request declined.';
        return response()->json(['status' => true, 'message' => $msg, 'data' => $shootRequest->fresh()]);
    }

    /**
     * Send a message in a shoot request thread.
     */
    public function sendMessage(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:tbl_product_shoot_requests,id',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $shootRequest = ProductShootRequest::findOrFail($request->request_id);

        // Determine sender role
        $role = null;
        if ($user->id === $shootRequest->creator_id) {
            $role = ShootRequestMessage::ROLE_CREATOR;
        } elseif ($user->id === $shootRequest->seller_id) {
            $role = ShootRequestMessage::ROLE_SELLER;
        } elseif ($user->is_moderator || $user->id === $shootRequest->admin_assigned_id) {
            $role = ShootRequestMessage::ROLE_ADMIN;
        }

        if (!$role) {
            return response()->json(['status' => false, 'message' => 'You are not a participant in this request.']);
        }

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('shoot_requests/' . $shootRequest->id, $filename, 'public');
                $attachments[] = [
                    'type' => str_starts_with($file->getMimeType(), 'image') ? 'image' : 'document',
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        $message = ShootRequestMessage::create([
            'request_id' => $shootRequest->id,
            'sender_id' => $user->id,
            'sender_role' => $role,
            'message' => $request->message,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Message sent.',
            'data' => $message->load('sender:id,username,fullname,profile_photo'),
        ]);
    }

    /**
     * Fetch messages for a shoot request.
     */
    public function fetchMessages(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $shootRequest = ProductShootRequest::findOrFail($request->request_id);

        // Verify participant
        $isParticipant = in_array($user->id, [$shootRequest->creator_id, $shootRequest->seller_id, $shootRequest->admin_assigned_id])
            || $user->is_moderator;

        if (!$isParticipant) {
            return response()->json(['status' => false, 'message' => 'Access denied.']);
        }

        $messages = ShootRequestMessage::where('request_id', $shootRequest->id)
            ->with('sender:id,username,fullname,profile_photo')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Messages fetched.',
            'data' => [
                'request' => $shootRequest->load('product:id,name,images', 'creator:id,username,fullname,profile_photo', 'seller:id,username,fullname,profile_photo'),
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Fetch my shoot requests (as creator or seller).
     */
    public function fetchMyRequests(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        $role = $request->input('role', 'creator'); // creator or seller
        $limit = min($request->input('limit', 20), 50);

        $query = ProductShootRequest::with([
            'product:id,name,images,price_paise',
            'creator:id,username,fullname,profile_photo',
            'seller:id,username,fullname,profile_photo',
        ]);

        if ($role === 'seller') {
            $query->where('seller_id', $user->id);
        } else {
            $query->where('creator_id', $user->id);
        }

        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $requests = $query->orderByDesc('created_at')->limit($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Requests fetched.',
            'data' => $requests,
        ]);
    }

    /**
     * Update shoot request status (for progression through workflow).
     */
    public function updateRequestStatus(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:tbl_product_shoot_requests,id',
            'status' => 'required|integer|min:0|max:9',
            'message' => 'nullable|string|max:2000',
            'sample_tracking_number' => 'nullable|string|max:100',
            'deliverable_post_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $shootRequest = ProductShootRequest::findOrFail($request->request_id);

        // Verify participant
        if (!in_array($user->id, [$shootRequest->creator_id, $shootRequest->seller_id]) && !$user->is_moderator) {
            return response()->json(['status' => false, 'message' => 'Access denied.']);
        }

        $updateData = ['status' => $request->status];

        if ($request->sample_tracking_number) {
            $updateData['sample_tracking_number'] = $request->sample_tracking_number;
        }
        if ($request->deliverable_post_id) {
            $updateData['deliverable_post_id'] = $request->deliverable_post_id;
        }

        $shootRequest->update($updateData);

        // Log status change as message
        if ($request->message) {
            $role = $user->id === $shootRequest->creator_id ? 'creator' : ($user->id === $shootRequest->seller_id ? 'seller' : 'admin');
            ShootRequestMessage::create([
                'request_id' => $shootRequest->id,
                'sender_id' => $user->id,
                'sender_role' => $role,
                'message' => $request->message,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Request status updated.',
            'data' => $shootRequest->fresh(),
        ]);
    }
}
