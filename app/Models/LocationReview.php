<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationReview extends Model
{
    protected $table = 'tbl_location_reviews';

    protected $fillable = [
        'user_id',
        'place_title',
        'place_lat',
        'place_lon',
        'rating',
        'review_text',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
