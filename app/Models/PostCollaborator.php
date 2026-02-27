<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCollaborator extends Model
{
    protected $table = 'post_collaborators';

    protected $fillable = [
        'post_id',
        'user_id',
        'invited_by',
        'status',
        'role',
        'credit_share',
    ];

    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_DECLINED = 2;

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function inviter()
    {
        return $this->belongsTo(Users::class, 'invited_by');
    }
}
