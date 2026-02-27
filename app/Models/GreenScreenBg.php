<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GreenScreenBg extends Model
{
    protected $table = 'tbl_green_screen_bgs';

    protected $fillable = [
        'title',
        'image',
        'video',
        'type',
        'category',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
