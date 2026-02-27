<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocation extends Model
{
    protected $table = 'tbl_user_locations';

    protected $fillable = [
        'user_id',
        'lat',
        'lon',
        'is_sharing',
        'location_updated_at',
    ];

    protected $casts = [
        'is_sharing' => 'boolean',
        'location_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
