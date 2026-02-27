<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateApplication extends Model
{
    protected $table = 'tbl_affiliate_applications';

    protected $fillable = [
        'user_id', 'follower_count', 'total_posts', 'total_views',
        'niche_category', 'social_links', 'bio', 'content_examples',
        'status', 'auto_approved', 'rejection_reason', 'admin_notes',
        'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'social_links' => 'array',
        'auto_approved' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_SUSPENDED = 3;

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
