<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    public $table = "tbl_appeals";

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
