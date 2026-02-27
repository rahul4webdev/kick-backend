<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentLanguage extends Model
{
    protected $table = 'tbl_content_languages';

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
