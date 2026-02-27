<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSticker extends Model
{
    protected $table = 'tbl_ai_stickers';

    protected $fillable = [
        'user_id',
        'prompt',
        'image_url',
        'is_public',
        'use_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
