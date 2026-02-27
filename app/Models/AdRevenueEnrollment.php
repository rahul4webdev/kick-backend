<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRevenueEnrollment extends Model
{
    protected $table = 'tbl_ad_revenue_enrollment';

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    protected $fillable = [
        'user_id',
        'status',
        'min_followers_at_enrollment',
        'min_views_at_enrollment',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
