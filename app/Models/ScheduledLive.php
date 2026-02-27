<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledLive extends Model
{
    protected $table = 'tbl_scheduled_lives';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'scheduled_at',
        'status',
        'reminder_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function reminders()
    {
        return $this->hasMany(ScheduledLiveReminder::class, 'scheduled_live_id');
    }
}
