<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerApplication extends Model
{
    protected $table = 'tbl_seller_applications';

    protected $fillable = [
        'user_id', 'seller_type', 'has_gst', 'gstin', 'gst_state_code',
        'pan_number', 'aadhaar_number', 'business_name', 'legal_business_name',
        'business_address', 'business_city', 'business_state', 'business_pincode', 'business_country',
        'bank_account_name', 'bank_account_number', 'bank_ifsc', 'bank_name', 'bank_branch',
        'pan_document', 'aadhaar_front_document', 'aadhaar_back_document',
        'gst_certificate_document', 'address_proof_document', 'cancelled_cheque_document',
        'business_license_document', 'brand_authorization_document', 'additional_documents',
        'fssai_license', 'drug_license',
        'status', 'rejection_reason', 'admin_notes', 'reviewed_by', 'reviewed_at',
        'razorpay_account_id', 'razorpay_account_status', 'cashfree_vendor_id',
        'shiprocket_pickup_location', 'shiprocket_pickup_id',
        'tcs_applicable', 'tds_applicable', 'fy_gross_sales_paise', 'current_fy',
    ];

    protected $casts = [
        'has_gst' => 'boolean',
        'tcs_applicable' => 'boolean',
        'tds_applicable' => 'boolean',
        'additional_documents' => 'array',
        'reviewed_at' => 'datetime',
        'fy_gross_sales_paise' => 'integer',
    ];

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_SUSPENDED = 3;
    const STATUS_REVOKED = 4;

    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_PROPRIETORSHIP = 'proprietorship';
    const TYPE_PARTNERSHIP = 'partnership';
    const TYPE_PRIVATE_LIMITED = 'private_limited';
    const TYPE_LLP = 'llp';

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
