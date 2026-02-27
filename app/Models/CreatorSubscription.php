<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatorSubscription extends Model
{
    protected $table = 'tbl_creator_subscriptions';

    protected $fillable = [
        'subscriber_id',
        'creator_id',
        'tier_id',
        'price_coins',
        'status',
        'auto_renew',
        'started_at',
        'expires_at',
        'cancelled_at',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_EXPIRED = 3;

    public function subscriber()
    {
        return $this->belongsTo(Users::class, 'subscriber_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function tier()
    {
        return $this->belongsTo(SubscriptionTier::class, 'tier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('expires_at', '>', now());
    }
}
