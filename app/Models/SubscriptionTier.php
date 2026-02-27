<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionTier extends Model
{
    protected $table = 'tbl_subscription_tiers';

    protected $fillable = [
        'creator_id',
        'name',
        'price_coins',
        'description',
        'benefits',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(CreatorSubscription::class, 'tier_id');
    }
}
