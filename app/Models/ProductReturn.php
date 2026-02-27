<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    protected $table = 'tbl_returns';

    protected $fillable = [
        'order_id', 'order_item_id', 'buyer_id', 'seller_id', 'product_id',
        'reason', 'description', 'photos', 'return_type',
        'status', 'seller_response', 'admin_notes', 'seller_inspection_photos',
        'refund_amount_paise', 'refund_method', 'refund_gateway_id',
        'shiprocket_return_order_id', 'return_awb', 'return_courier',
        'approved_at', 'pickup_scheduled_at', 'received_at',
        'refund_initiated_at', 'refund_completed_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'seller_inspection_photos' => 'array',
        'refund_amount_paise' => 'integer',
        'approved_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'received_at' => 'datetime',
        'refund_initiated_at' => 'datetime',
        'refund_completed_at' => 'datetime',
    ];

    const STATUS_REQUESTED = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PICKUP_SCHEDULED = 3;
    const STATUS_IN_TRANSIT = 4;
    const STATUS_RECEIVED_BY_SELLER = 5;
    const STATUS_INSPECTION_PASSED = 6;
    const STATUS_INSPECTION_FAILED = 7;
    const STATUS_REFUND_INITIATED = 8;
    const STATUS_REFUND_COMPLETED = 9;
    const STATUS_REPLACEMENT_SHIPPED = 10;

    const REASON_DEFECTIVE = 'defective';
    const REASON_WRONG_ITEM = 'wrong_item';
    const REASON_NOT_AS_DESCRIBED = 'not_as_described';
    const REASON_SIZE_ISSUE = 'size_issue';
    const REASON_CHANGE_OF_MIND = 'change_of_mind';
    const REASON_DAMAGED_IN_TRANSIT = 'damaged_in_transit';
    const REASON_OTHER = 'other';

    const TYPE_REFUND = 'refund';
    const TYPE_REPLACEMENT = 'replacement';
    const TYPE_EXCHANGE = 'exchange';

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Users::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
