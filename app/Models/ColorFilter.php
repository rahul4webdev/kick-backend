<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorFilter extends Model
{
    use HasFactory;

    public $table = 'color_filters';

    protected $casts = [
        'color_matrix' => 'array',
        'status' => 'boolean',
    ];
}
