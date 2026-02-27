<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwoFaToken extends Model
{
    public $table = 'tbl_two_fa_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
