<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdImpression extends Model
{
    protected $table = 'tbl_ad_impressions';

    protected $fillable = [
        'post_id',
        'creator_id',
        'viewer_id',
        'ad_type',
        'ad_network',
        'estimated_revenue',
        'platform',
    ];

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }
}
