<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryChain extends Model
{
    protected $table = 'tbl_story_chains';

    protected $fillable = [
        'prompt',
        'creator_id',
        'origin_story_id',
        'participant_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function originStory()
    {
        return $this->belongsTo(Story::class, 'origin_story_id');
    }

    public function participants()
    {
        return $this->hasMany(StoryChainParticipant::class, 'chain_id');
    }
}
