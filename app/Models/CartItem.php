<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'tbl_cart_items';

    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    /**
     * Get effective price in paise for this cart item.
     */
    public function getPricePaise(): int
    {
        if ($this->variant_id && $this->variant) {
            return $this->variant->getEffectivePricePaise();
        }
        return $this->product->price_paise ?? 0;
    }

    /**
     * Get line total in paise.
     */
    public function getLineTotalPaise(): int
    {
        return $this->getPricePaise() * $this->quantity;
    }
}
