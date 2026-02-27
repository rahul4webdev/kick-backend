<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryChainParticipant extends Model
{
    protected $table = 'tbl_story_chain_participants';

    protected $fillable = [
        'chain_id',
        'story_id',
        'user_id',
    ];

    public function chain()
    {
        return $this->belongsTo(StoryChain::class, 'chain_id');
    }

    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
