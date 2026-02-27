<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidSeriesPurchase extends Model
{
    use HasFactory;

    protected $table = 'tbl_paid_series_purchases';

    protected $fillable = [
        'series_id',
        'user_id',
        'amount_coins',
        'transaction_id',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    public function series()
    {
        return $this->belongsTo(PaidSeries::class, 'series_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
