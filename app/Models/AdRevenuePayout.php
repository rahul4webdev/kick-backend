<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRevenuePayout extends Model
{
    protected $table = 'tbl_ad_revenue_payouts';

    const STATUS_PENDING = 0;
    const STATUS_PROCESSED = 1;
    const STATUS_PAID = 2;

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'total_impressions',
        'total_estimated_revenue',
        'creator_share',
        'platform_share',
        'coins_credited',
        'transaction_id',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(CoinTransaction::class, 'transaction_id');
    }
}
