<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'tbl_templates';

    protected $fillable = [
        'name',
        'description',
        'thumbnail',
        'preview_video',
        'clip_count',
        'duration_sec',
        'category',
        'music_id',
        'transition_data',
        'is_active',
        'use_count',
        'sort_order',
        'creator_id',
        'is_user_created',
        'source_post_id',
        'trending_score',
        'like_count',
    ];

    protected $casts = [
        'transition_data' => 'array',
        'is_active' => 'boolean',
        'is_user_created' => 'boolean',
    ];

    public function clips()
    {
        return $this->hasMany(TemplateClip::class, 'template_id', 'id')->orderBy('clip_index');
    }

    public function music()
    {
        return $this->hasOne(Musics::class, 'id', 'music_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id', 'id');
    }
}
