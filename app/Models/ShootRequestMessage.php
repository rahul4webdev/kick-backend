<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShootRequestMessage extends Model
{
    protected $table = 'tbl_shoot_request_messages';

    public $timestamps = false; // only created_at

    protected $fillable = [
        'request_id', 'sender_id', 'sender_role', 'message', 'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
    ];

    const ROLE_CREATOR = 'creator';
    const ROLE_SELLER = 'seller';
    const ROLE_ADMIN = 'admin';

    public function request()
    {
        return $this->belongsTo(ProductShootRequest::class, 'request_id');
    }

    public function sender()
    {
        return $this->belongsTo(Users::class, 'sender_id');
    }
}
