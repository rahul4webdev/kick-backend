<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardedAdClaim extends Model
{
    use HasFactory;

    protected $table = 'tbl_rewarded_ad_claims';

    protected $fillable = [
        'user_id',
        'claim_date',
        'claim_count',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
