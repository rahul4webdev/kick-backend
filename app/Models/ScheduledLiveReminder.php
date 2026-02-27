<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledLiveReminder extends Model
{
    protected $table = 'tbl_scheduled_live_reminders';

    protected $fillable = [
        'scheduled_live_id',
        'user_id',
    ];

    public function scheduledLive()
    {
        return $this->belongsTo(ScheduledLive::class, 'scheduled_live_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
