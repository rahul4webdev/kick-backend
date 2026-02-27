<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $table = 'tbl_order_status_history';

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'status', 'status_label', 'description', 'location',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }

    /**
     * Record a status change for an order.
     */
    public static function record(int $orderId, int $status, string $label, ?string $description = null, ?string $location = null): self
    {
        return self::create([
            'order_id' => $orderId,
            'status' => $status,
            'status_label' => $label,
            'description' => $description,
            'location' => $location,
        ]);
    }
}
