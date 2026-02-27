<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_reviews';

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'review_text',
        'photos',
        'is_verified_purchase',
    ];

    protected $casts = [
        'photos' => 'array',
        'is_verified_purchase' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
