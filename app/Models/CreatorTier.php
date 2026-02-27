<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatorTier extends Model
{
    protected $table = 'tbl_creator_tiers';

    protected $fillable = [
        'name', 'level', 'min_followers', 'min_total_views',
        'min_total_likes', 'commission_rate', 'badge_color', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
    ];
}
