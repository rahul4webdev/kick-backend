<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryInteraction extends Model
{
    public $table = "tbl_story_interactions";

    protected $fillable = [
        'story_id',
        'user_id',
        'interaction_type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
