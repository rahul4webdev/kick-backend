<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'tbl_product_variants';

    protected $fillable = [
        'product_id', 'variant_type', 'size_value', 'color_value', 'color_hex',
        'sku', 'price_paise', 'stock', 'sold_count', 'images',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'price_paise' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the effective price (variant price or product base price).
     */
    public function getEffectivePricePaise(): int
    {
        return $this->price_paise ?? $this->product->price_paise;
    }

    /**
     * Get a human-readable label like "Size: M, Color: Red".
     */
    public function getLabel(): string
    {
        $parts = [];
        if ($this->size_value) $parts[] = "Size: {$this->size_value}";
        if ($this->color_value) $parts[] = "Color: {$this->color_value}";
        return implode(', ', $parts);
    }

    /**
     * Check stock availability.
     */
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock === -1 || $this->stock >= $quantity;
    }
}
