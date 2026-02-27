<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    protected $table = 'tbl_portfolios';

    protected $casts = [
        'custom_colors' => 'array',
        'featured_post_ids' => 'array',
        'is_active' => 'boolean',
        'show_products' => 'boolean',
        'show_links' => 'boolean',
        'show_subscription_cta' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function sections()
    {
        return $this->hasMany(PortfolioSection::class, 'portfolio_id', 'id')
            ->orderBy('sort_order');
    }
}
