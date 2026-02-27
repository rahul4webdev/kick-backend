<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerPayout extends Model
{
    protected $table = 'tbl_seller_payouts';

    protected $fillable = [
        'seller_id', 'gross_amount_paise', 'platform_commission_paise',
        'tcs_deducted_paise', 'tds_deducted_paise', 'return_deductions_paise',
        'net_amount_paise', 'payout_method', 'razorpay_transfer_id',
        'bank_reference', 'utr_number', 'status', 'failure_reason',
        'period_start', 'period_end', 'order_count', 'order_ids', 'notes',
    ];

    protected $casts = [
        'order_ids' => 'array',
        'gross_amount_paise' => 'integer',
        'net_amount_paise' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;
    const STATUS_ON_HOLD = 4;

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }
}
