<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posts extends Model
{
    use HasFactory;
    public $table = "tbl_post";

    public function user()
    {
        return $this->hasOne(Users::class, 'id', 'user_id');
    }
    public function music()
    {
        return $this->hasOne(Musics::class, 'id', 'sound_id');
    }
    public function images()
    {
        return $this->hasMany(PostImages::class, 'post_id', 'id');
    }
    public function comments()
    {
        return $this->hasMany(PostComments::class, 'post_id', 'id');
    }

    public function duetSource()
    {
        return $this->belongsTo(Posts::class, 'duet_source_post_id', 'id');
    }

    public function duets()
    {
        return $this->hasMany(Posts::class, 'duet_source_post_id', 'id');
    }

    public function stitchSource()
    {
        return $this->belongsTo(Posts::class, 'stitch_source_post_id', 'id');
    }

    public function stitches()
    {
        return $this->hasMany(Posts::class, 'stitch_source_post_id', 'id');
    }

    public function collaborators()
    {
        return $this->hasMany(PostCollaborator::class, 'post_id', 'id')
            ->where('status', PostCollaborator::STATUS_ACCEPTED);
    }

    public function productTags()
    {
        return $this->hasMany(PostProductTag::class, 'post_id', 'id');
    }
}
