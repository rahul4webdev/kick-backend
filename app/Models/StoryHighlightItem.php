<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryHighlightItem extends Model
{
    public $table = "tbl_story_highlight_items";

    protected $fillable = ['highlight_id', 'original_story_id', 'type', 'content', 'thumbnail', 'duration', 'sort_order'];

    public function highlight()
    {
        return $this->belongsTo(StoryHighlight::class, 'highlight_id', 'id');
    }
}
