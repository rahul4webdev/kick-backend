<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceCampaign extends Model
{
    protected $table = 'tbl_marketplace_campaigns';

    const STATUS_DRAFT = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_PAUSED = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_CANCELLED = 5;

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Users::class, 'brand_user_id');
    }

    public function proposals()
    {
        return $this->hasMany(MarketplaceProposal::class, 'campaign_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }
}
