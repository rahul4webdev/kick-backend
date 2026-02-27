<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LivestreamReplay extends Model
{
    use HasFactory;

    protected $table = 'tbl_livestream_replays';

    protected $fillable = [
        'user_id',
        'room_id',
        'title',
        'thumbnail',
        'recording_url',
        'duration_seconds',
        'peak_viewers',
        'total_likes',
        'total_gifts_coins',
        'view_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
