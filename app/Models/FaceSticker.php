<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceSticker extends Model
{
    use HasFactory;

    public $table = 'face_stickers';

    protected $casts = [
        'status' => 'boolean',
    ];
}
