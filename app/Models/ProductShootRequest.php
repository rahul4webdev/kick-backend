<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductShootRequest extends Model
{
    protected $table = 'tbl_product_shoot_requests';

    protected $fillable = [
        'creator_id', 'seller_id', 'product_id', 'request_type',
        'title', 'description',
        'delivery_address', 'delivery_city', 'delivery_state', 'delivery_pincode',
        'sample_tracking_number', 'security_deposit_paise',
        'proposed_date', 'proposed_location',
        'status', 'admin_assigned_id', 'admin_in_conversation',
        'deliverable_post_id',
    ];

    protected $casts = [
        'admin_in_conversation' => 'boolean',
        'proposed_date' => 'date',
        'security_deposit_paise' => 'integer',
    ];

    const TYPE_SAMPLE_DELIVERY = 'sample_delivery';
    const TYPE_ONSITE_VISIT = 'onsite_visit';

    const STATUS_PENDING = 0;
    const STATUS_SELLER_ACCEPTED = 1;
    const STATUS_SELLER_DECLINED = 2;
    const STATUS_IN_PROGRESS = 3;
    const STATUS_SAMPLE_SHIPPED = 4;
    const STATUS_SAMPLE_RECEIVED = 5;
    const STATUS_SHOOT_COMPLETED = 6;
    const STATUS_SAMPLE_RETURNED = 7;
    const STATUS_COMPLETED = 8;
    const STATUS_CANCELLED = 9;

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function messages()
    {
        return $this->hasMany(ShootRequestMessage::class, 'request_id');
    }
}
