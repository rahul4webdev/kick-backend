<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $table = 'tbl_ai_chat_messages';

    protected $fillable = [
        'user_id',
        'session_id',
        'user_message',
        'ai_response',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
