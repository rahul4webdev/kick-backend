<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveChannel extends Model
{
    use HasFactory;

    protected $table = 'tbl_live_channels';

    protected $fillable = [
        'user_id',
        'channel_name',
        'channel_logo',
        'stream_url',
        'stream_type',
        'category',
        'language',
        'is_live',
        'is_active',
        'viewer_count',
        'sort_order',
    ];

    protected $casts = [
        'is_live' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
