<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidSeriesVideo extends Model
{
    use HasFactory;

    protected $table = 'tbl_paid_series_videos';

    protected $fillable = [
        'series_id',
        'post_id',
        'position',
    ];

    public function series()
    {
        return $this->belongsTo(PaidSeries::class, 'series_id');
    }

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }
}
