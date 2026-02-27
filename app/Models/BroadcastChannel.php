<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastChannel extends Model
{
    protected $table = 'tbl_broadcast_channels';
    protected $guarded = [];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function members()
    {
        return $this->hasMany(BroadcastMember::class, 'channel_id');
    }
}
