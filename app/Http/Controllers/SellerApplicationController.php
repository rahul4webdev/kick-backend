<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\SellerApplication;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SellerApplicationController extends Controller
{
    // ═══════════════════════════════════════════
    //  USER API ENDPOINTS
    // ═══════════════════════════════════════════

    /**
     * Submit seller application with KYC documents.
     */
    public function submitApplication(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        // Check if already has a pending or approved application
        $existing = SellerApplication::where('user_id', $user->id)
            ->whereIn('status', [SellerApplication::STATUS_PENDING, SellerApplication::STATUS_APPROVED])
            ->first();

        if ($existing) {
            $msg = $existing->isApproved()
                ? 'You are already an approved seller.'
                : 'You already have a pending application.';
            return response()->json(['status' => false, 'message' => $msg]);
        }

        // Base validation
        $rules = [
            'seller_type' => 'required|in:individual,proprietorship,partnership,private_limited,llp',
            'has_gst' => 'required|boolean',
            'pan_number' => ['required', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'business_address' => 'required|string|max:500',
            'business_city' => 'required|string|max:100',
            'business_state' => 'required|string|max:100',
            'business_pincode' => ['required', 'string', 'size:6', 'regex:/^[1-9][0-9]{5}$/'],
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_ifsc' => ['required', 'string', 'size:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
        ];

        // GST-specific validation
        if ($request->has_gst) {
            $rules['gstin'] = ['required', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'];
            $rules['gst_state_code'] = 'required|string|max:5';
        }

        // Individual-specific validation
        if ($request->seller_type === 'individual') {
            $rules['aadhaar_number'] = ['required', 'string', 'size:12', 'regex:/^[2-9][0-9]{11}$/'];
        }

        // Business name for non-individuals
        if ($request->seller_type !== 'individual') {
            $rules['business_name'] = 'required|string|max:255';
            $rules['legal_business_name'] = 'required|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        // Handle document uploads
        $documentFields = [
            'pan_document', 'aadhaar_front_document', 'aadhaar_back_document',
            'gst_certificate_document', 'address_proof_document',
            'cancelled_cheque_document', 'business_license_document',
            'brand_authorization_document',
        ];

        $documentPaths = [];
        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('seller_documents/' . $user->id, $filename, 'public');
                $documentPaths[$field] = $path;
            }
        }

        // Mandatory document checks
        if (!isset($documentPaths['pan_document']) && !$request->pan_document) {
            return response()->json(['status' => false, 'message' => 'PAN card document is required.']);
        }
        if (!isset($documentPaths['cancelled_cheque_document']) && !$request->cancelled_cheque_document) {
            return response()->json(['status' => false, 'message' => 'Cancelled cheque or bank passbook is required.']);
        }
        if ($request->seller_type === 'individual') {
            if (!isset($documentPaths['aadhaar_front_document']) && !$request->aadhaar_front_document) {
                return response()->json(['status' => false, 'message' => 'Aadhaar front document is required.']);
            }
        }
        if ($request->has_gst) {
            if (!isset($documentPaths['gst_certificate_document']) && !$request->gst_certificate_document) {
                return response()->json(['status' => false, 'message' => 'GST certificate is required.']);
            }
        }

        // Create application
        $application = SellerApplication::create(array_merge(
            $request->only([
                'seller_type', 'has_gst', 'gstin', 'gst_state_code',
                'pan_number', 'aadhaar_number', 'business_name', 'legal_business_name',
                'business_address', 'business_city', 'business_state', 'business_pincode',
                'bank_account_name', 'bank_account_number', 'bank_ifsc', 'bank_name', 'bank_branch',
                'fssai_license', 'drug_license',
            ]),
            $documentPaths,
            [
                'user_id' => $user->id,
                'status' => SellerApplication::STATUS_PENDING,
                'tcs_applicable' => (bool) $request->has_gst, // GST sellers get TCS
            ]
        ));

        return response()->json([
            'status' => true,
            'message' => 'Seller application submitted successfully. Our team will review your documents.',
            'data' => $application,
        ]);
    }

    /**
     * Fetch current user's seller application status.
     */
    public function fetchMyApplication(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $application = SellerApplication::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'status' => true,
            'message' => $application ? 'Application fetched' : 'No application found',
            'data' => $application,
        ]);
    }

    /**
     * Update seller bank details (approved sellers only).
     */
    public function updateBankDetails(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $application = SellerApplication::where('user_id', $user->id)
            ->where('status', SellerApplication::STATUS_APPROVED)
            ->first();

        if (!$application) {
            return response()->json(['status' => false, 'message' => 'No approved seller account found.']);
        }

        $rules = [
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'bank_ifsc' => ['required', 'string', 'size:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => 'nullable|string|max:150',
            'bank_branch' => 'nullable|string|max:200',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $application->update($request->only([
            'bank_account_name', 'bank_account_number', 'bank_ifsc', 'bank_name', 'bank_branch',
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Bank details updated successfully.',
            'data' => $application->fresh(),
        ]);
    }

    /**
     * Update seller address (approved sellers only).
     */
    public function updateBusinessAddress(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $application = SellerApplication::where('user_id', $user->id)
            ->where('status', SellerApplication::STATUS_APPROVED)
            ->first();

        if (!$application) {
            return response()->json(['status' => false, 'message' => 'No approved seller account found.']);
        }

        $validator = Validator::make($request->all(), [
            'business_address' => 'required|string|max:500',
            'business_city' => 'required|string|max:100',
            'business_state' => 'required|string|max:100',
            'business_pincode' => ['required', 'string', 'size:6', 'regex:/^[1-9][0-9]{5}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $application->update($request->only([
            'business_address', 'business_city', 'business_state', 'business_pincode',
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Business address updated.',
            'data' => $application->fresh(),
        ]);
    }

    // ═══════════════════════════════════════════
    //  ADMIN API ENDPOINTS
    // ═══════════════════════════════════════════

    /**
     * Admin: List all seller applications (DataTable).
     */
    public function listApplications_Admin(Request $request)
    {
        $start = $request->input('start', 0);
        $length = $request->input('length', 20);
        $search = $request->input('search')['value'] ?? '';
        $statusFilter = $request->input('status_filter', null);

        $query = SellerApplication::with('user:id,username,fullname,profile_photo');

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'ILIKE', "%{$search}%")
                    ->orWhere('pan_number', 'ILIKE', "%{$search}%")
                    ->orWhere('gstin', 'ILIKE', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'ILIKE', "%{$search}%")
                            ->orWhere('fullname', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $total = SellerApplication::count();
        $filtered = $query->count();
        $applications = $query->orderByDesc('created_at')
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $applications,
        ]);
    }

    /**
     * Admin: Approve seller application.
     */
    public function approveApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:tbl_seller_applications,id',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $application = SellerApplication::findOrFail($request->application_id);

        if ($application->status !== SellerApplication::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Application is not in pending status.']);
        }

        DB::transaction(function () use ($application, $request) {
            $application->update([
                'status' => SellerApplication::STATUS_APPROVED,
                'admin_notes' => $request->admin_notes,
                'reviewed_by' => null,
                'reviewed_at' => now(),
            ]);

            // Update user flags
            Users::where('id', $application->user_id)->update([
                'is_approved_seller' => true,
            ]);

            // If user isn't already a business account, upgrade them
            $user = Users::find($application->user_id);
            if ($user && $user->account_type < Constants::accountTypeBusiness) {
                $user->update([
                    'account_type' => Constants::accountTypeBusiness,
                    'business_status' => Constants::businessStatusApproved,
                ]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Seller application approved.',
            'data' => $application->fresh(),
        ]);
    }

    /**
     * Admin: Reject seller application.
     */
    public function rejectApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:tbl_seller_applications,id',
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $application = SellerApplication::findOrFail($request->application_id);

        if ($application->status !== SellerApplication::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Application is not in pending status.']);
        }

        $application->update([
            'status' => SellerApplication::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason,
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => null,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Seller application rejected.',
            'data' => $application->fresh(),
        ]);
    }

    /**
     * Admin: Suspend an approved seller.
     */
    public function suspendSeller(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:tbl_seller_applications,id',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $application = SellerApplication::findOrFail($request->application_id);

        if ($application->status !== SellerApplication::STATUS_APPROVED) {
            return response()->json(['status' => false, 'message' => 'Only approved sellers can be suspended.']);
        }

        DB::transaction(function () use ($application, $request) {
            $application->update([
                'status' => SellerApplication::STATUS_SUSPENDED,
                'admin_notes' => $request->reason,
            ]);

            Users::where('id', $application->user_id)->update([
                'is_approved_seller' => false,
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Seller suspended.',
        ]);
    }

    /**
     * Admin: View full application details.
     */
    public function fetchApplicationDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:tbl_seller_applications,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $application = SellerApplication::with('user:id,username,fullname,profile_photo,account_type,business_status,is_verify')
            ->findOrFail($request->application_id);

        return response()->json([
            'status' => true,
            'message' => 'Application details fetched.',
            'data' => $application,
        ]);
    }

    /**
     * Admin: Blade view for seller applications.
     */
    public function sellerApplicationsAdmin()
    {
        return view('seller_applications');
    }
}
