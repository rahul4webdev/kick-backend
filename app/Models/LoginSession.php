<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginSession extends Model
{
    protected $table = 'tbl_login_sessions';
    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'logged_in_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
