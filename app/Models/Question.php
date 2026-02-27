<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'tbl_questions';
    protected $guarded = [];

    public function profileUser()
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function askedBy()
    {
        return $this->belongsTo(User::class, 'asked_by_user_id');
    }
}
