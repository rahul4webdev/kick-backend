<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'tbl_products';

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'description',
        'price_coins',
        'price_paise',
        'compare_at_price_paise',
        'shipping_charge_paise',
        'gst_rate',
        'hsn_code',
        'images',
        'stock',
        'sold_count',
        'rating_count',
        'avg_rating',
        'view_count',
        'status',
        'is_active',
        'is_digital',
        'digital_file',
        'weight_grams',
        'length_cm',
        'breadth_cm',
        'height_cm',
        'has_variants',
        'sku',
        'brand_name',
        'min_order_qty',
        'max_order_qty',
        'shipping_type',
        'cod_available',
        'return_window_days_override',
        'return_type_override',
        'pickup_location_name',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'is_digital' => 'boolean',
        'has_variants' => 'boolean',
        'cod_available' => 'boolean',
        'avg_rating' => 'float',
        'gst_rate' => 'float',
        'price_paise' => 'integer',
        'compare_at_price_paise' => 'integer',
        'shipping_charge_paise' => 'integer',
        'weight_grams' => 'integer',
    ];

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    const SHIPPING_SELF = 'self';
    const SHIPPING_PLATFORM = 'platform';
    const SHIPPING_BOTH = 'both';

    public function seller()
    {
        return $this->belongsTo(Users::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function orders()
    {
        return $this->hasMany(ProductOrder::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Get effective price in paise (variant-aware).
     */
    public function getEffectivePricePaise(?int $variantId = null): int
    {
        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) {
                return $variant->getEffectivePricePaise();
            }
        }
        return $this->price_paise ?? 0;
    }

    /**
     * Get return window days (product override > category default > 7).
     */
    public function getReturnWindowDays(): int
    {
        if ($this->return_window_days_override !== null) {
            return $this->return_window_days_override;
        }
        if ($this->category) {
            return $this->category->return_window_days ?? 7;
        }
        return 7;
    }

    /**
     * Is this product returnable?
     */
    public function isReturnable(): bool
    {
        if ($this->category) {
            return (bool) ($this->category->returnable ?? true);
        }
        return true;
    }

    /**
     * Get commission rate for this product.
     */
    public function getCommissionRate(): float
    {
        if ($this->category && $this->category->commission_rate !== null) {
            return (float) $this->category->commission_rate;
        }
        $settings = \Illuminate\Support\Facades\DB::table('tbl_settings')->first();
        return (float) ($settings->default_commission_rate ?? 20.00);
    }

    /**
     * Check if has stock (variant-aware).
     */
    public function hasStock(int $quantity = 1, ?int $variantId = null): bool
    {
        if ($variantId && $this->has_variants) {
            $variant = $this->variants()->find($variantId);
            return $variant && $variant->hasStock($quantity);
        }
        return $this->stock == -1 || $this->stock >= $quantity;
    }
}
