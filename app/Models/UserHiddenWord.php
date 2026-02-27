<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHiddenWord extends Model
{
    use HasFactory;

    public $table = "tbl_user_hidden_words";

    protected $fillable = ['user_id', 'word'];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
