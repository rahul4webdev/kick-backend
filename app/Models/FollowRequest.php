<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowRequest extends Model
{
    use HasFactory;
    public $table = "follow_requests";

    protected $fillable = ['from_user_id', 'to_user_id', 'status'];

    public function from_user()
    {
        return $this->belongsTo(Users::class, 'from_user_id', 'id');
    }

    public function to_user()
    {
        return $this->belongsTo(Users::class, 'to_user_id', 'id');
    }
}
