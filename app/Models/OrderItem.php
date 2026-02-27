<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'tbl_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price_coins',
        'price_paise',
        'variant_label',
    ];

    protected $casts = [
        'price_paise' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
