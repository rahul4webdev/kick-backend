<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMute extends Model
{
    public $table = "tbl_user_mute";

    protected $casts = [
        'mute_posts' => 'boolean',
        'mute_stories' => 'boolean',
    ];

    public function from_user()
    {
        return $this->hasOne(Users::class, 'id', 'from_user_id');
    }

    public function to_user()
    {
        return $this->hasOne(Users::class, 'id', 'to_user_id');
    }
}
