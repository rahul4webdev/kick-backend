<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioSection extends Model
{
    use HasFactory;

    protected $table = 'tbl_portfolio_sections';

    protected $casts = [
        'data' => 'array',
        'is_visible' => 'boolean',
    ];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id', 'id');
    }
}
