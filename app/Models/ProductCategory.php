<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_categories';

    protected $fillable = [
        'name',
        'icon',
        'sort_order',
        'is_active',
        'commission_rate',
        'return_window_days',
        'return_type',
        'returnable',
        'hsn_code_prefix',
        'default_gst_rate',
        'description',
        'image',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'returnable' => 'boolean',
        'commission_rate' => 'float',
        'default_gst_rate' => 'float',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
