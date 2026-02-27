<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repost extends Model
{
    protected $table = 'tbl_reposts';

    protected $fillable = [
        'user_id', 'original_post_id', 'caption',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function originalPost()
    {
        return $this->belongsTo(Posts::class, 'original_post_id');
    }
}
