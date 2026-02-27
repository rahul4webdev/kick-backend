<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistPost extends Model
{
    protected $table = 'tbl_playlist_posts';
    protected $guarded = [];

    public function playlist()
    {
        return $this->belongsTo(Playlist::class, 'playlist_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
