<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipAmount extends Model
{
    protected $table = 'tbl_tip_amounts';

    protected $fillable = [
        'coins', 'label', 'emoji', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
