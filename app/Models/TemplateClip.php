<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateClip extends Model
{
    protected $table = 'tbl_template_clips';

    protected $fillable = [
        'template_id',
        'clip_index',
        'duration_ms',
        'label',
        'transition_to_next',
        'transition_duration_ms',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id', 'id');
    }
}
