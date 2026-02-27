<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramImports extends Model
{
    public $table = 'tbl_instagram_imports';

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id', 'id');
    }
}
