<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNote extends Model
{
    protected $table = 'user_notes';

    protected $fillable = [
        'user_id',
        'content',
        'emoji',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
