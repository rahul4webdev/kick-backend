<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryHighlight extends Model
{
    public $table = "tbl_story_highlights";

    protected $fillable = ['user_id', 'name', 'cover_image', 'sort_order', 'item_count'];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(StoryHighlightItem::class, 'highlight_id', 'id')->orderBy('sort_order');
    }
}
