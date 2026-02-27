<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    protected $table = 'tbl_playlists';
    protected $guarded = [];
    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'tbl_playlist_posts', 'playlist_id', 'post_id')
            ->withPivot('position')
            ->orderBy('tbl_playlist_posts.position', 'asc');
    }

    public function playlistPosts()
    {
        return $this->hasMany(PlaylistPost::class, 'playlist_id');
    }

    public function coverPost()
    {
        return $this->hasOne(PlaylistPost::class, 'playlist_id')
            ->orderBy('position', 'asc');
    }
}
