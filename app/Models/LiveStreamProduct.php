<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveStreamProduct extends Model
{
    protected $table = 'tbl_live_stream_products';

    protected $fillable = [
        'room_id',
        'product_id',
        'seller_id',
        'position',
        'is_pinned',
        'units_sold',
        'revenue_coins',
        'is_active',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }
}
