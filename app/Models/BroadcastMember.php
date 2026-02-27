<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastMember extends Model
{
    protected $table = 'tbl_broadcast_members';
    protected $guarded = [];
    public $timestamps = false;

    public function channel()
    {
        return $this->belongsTo(BroadcastChannel::class, 'channel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
