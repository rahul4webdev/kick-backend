<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $table = 'tbl_payment_transactions';

    protected $fillable = [
        'user_id', 'order_id', 'gateway', 'gateway_order_id', 'gateway_payment_id',
        'gateway_signature', 'amount_paise', 'currency', 'status',
        'payment_method', 'payment_details', 'refund_amount_paise', 'refund_id',
        'refunded_at', 'metadata', 'failure_reason',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'metadata' => 'array',
        'amount_paise' => 'integer',
        'refund_amount_paise' => 'integer',
        'refunded_at' => 'datetime',
    ];

    const GATEWAY_RAZORPAY = 'razorpay';
    const GATEWAY_CASHFREE = 'cashfree';
    const GATEWAY_PHONEPE = 'phonepe';

    const STATUS_CREATED = 'created';
    const STATUS_AUTHORIZED = 'authorized';
    const STATUS_CAPTURED = 'captured';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    const METHOD_UPI = 'upi';
    const METHOD_CARD = 'card';
    const METHOD_NETBANKING = 'netbanking';
    const METHOD_WALLET = 'wallet';
    const METHOD_COD = 'cod';
    const METHOD_EMI = 'emi';

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }

    public function isCaptured(): bool
    {
        return $this->status === self::STATUS_CAPTURED;
    }

    /**
     * Amount in rupees (for display).
     */
    public function getAmountRupees(): float
    {
        return $this->amount_paise / 100;
    }
}
