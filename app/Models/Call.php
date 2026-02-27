<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $table = 'tbl_calls';

    protected $fillable = [
        'room_id',
        'caller_id',
        'call_type',
        'status',
        'is_group',
        'started_at',
        'ended_at',
        'duration_sec',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function caller()
    {
        return $this->belongsTo(Users::class, 'caller_id');
    }

    public function participants()
    {
        return $this->hasMany(CallParticipant::class, 'call_id');
    }
}
