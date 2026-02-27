<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentGenre extends Model
{
    protected $table = 'tbl_content_genres';

    protected $fillable = [
        'name',
        'content_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
