<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidSeries extends Model
{
    use HasFactory;

    protected $table = 'tbl_paid_series';

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'cover_image',
        'price_coins',
        'video_count',
        'purchase_count',
        'total_revenue',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function videos()
    {
        return $this->hasMany(PaidSeriesVideo::class, 'series_id')->orderBy('position');
    }

    public function purchases()
    {
        return $this->hasMany(PaidSeriesPurchase::class, 'series_id');
    }
}
