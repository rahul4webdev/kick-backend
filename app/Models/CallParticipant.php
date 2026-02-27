<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallParticipant extends Model
{
    protected $table = 'tbl_call_participants';

    protected $fillable = [
        'call_id',
        'user_id',
        'status',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function call()
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
