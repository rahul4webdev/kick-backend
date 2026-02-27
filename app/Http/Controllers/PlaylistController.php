<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Playlist;
use App\Models\PlaylistPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlaylistController extends Controller
{
    public function fetchUserPlaylists(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $targetUserId = $request->user_id;
        $isOwner = $user->id == $targetUserId;

        $query = Playlist::where('user_id', $targetUserId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');

        // Non-owners only see public playlists
        if (!$isOwner) {
            $query->where('is_public', true);
        }

        $playlists = $query->get();

        // Attach cover thumbnail from first post
        foreach ($playlists as $playlist) {
            $firstPost = PlaylistPost::where('playlist_id', $playlist->id)
                ->orderBy('position', 'asc')
                ->first();
            if ($firstPost) {
                $post = \App\Models\Post::select('id', 'thumbnail', 'post_type')->find($firstPost->post_id);
                $playlist->cover_thumbnail = $post?->thumbnail;
            } else {
                $playlist->cover_thumbnail = null;
            }
        }

        return GlobalFunction::sendDataResponse(true, 'playlists fetched', $playlists);
    }

    public function createPlaylist(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        // Limit to 20 playlists per user
        $count = Playlist::where('user_id', $user->id)->count();
        if ($count >= 20) {
            return GlobalFunction::sendSimpleResponse(false, 'Maximum 20 playlists allowed');
        }

        $playlist = new Playlist();
        $playlist->user_id = $user->id;
        $playlist->name = $request->name;
        $playlist->description = $request->description;
        $playlist->is_public = $request->is_public ?? true;
        $playlist->sort_order = $count;
        $playlist->save();

        return GlobalFunction::sendDataResponse(true, 'Playlist created', $playlist);
    }

    public function updatePlaylist(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'playlist_id' => 'required',
            'name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $playlist = Playlist::where('id', $request->playlist_id)
            ->where('user_id', $user->id)->first();
        if (!$playlist) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        if ($request->has('name')) $playlist->name = $request->name;
        if ($request->has('description')) $playlist->description = $request->description;
        if ($request->has('is_public')) $playlist->is_public = $request->is_public;
        $playlist->save();

        return GlobalFunction::sendSimpleResponse(true, 'Playlist updated');
    }

    public function deletePlaylist(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $playlist = Playlist::where('id', $request->playlist_id)
            ->where('user_id', $user->id)->first();
        if (!$playlist) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        $playlist->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Playlist deleted');
    }

    public function addPostToPlaylist(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'playlist_id' => 'required',
            'post_id' => 'required|exists:tbl_post,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $playlist = Playlist::where('id', $request->playlist_id)
            ->where('user_id', $user->id)->first();
        if (!$playlist) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        // Check post belongs to user
        $post = \App\Models\Post::where('id', $request->post_id)
            ->where('user_id', $user->id)->first();
        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'You can only add your own posts');
        }

        // Check if already in playlist
        $exists = PlaylistPost::where('playlist_id', $playlist->id)
            ->where('post_id', $request->post_id)->exists();
        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'Post already in playlist');
        }

        // Get next position
        $maxPos = PlaylistPost::where('playlist_id', $playlist->id)->max('position') ?? -1;

        $pp = new PlaylistPost();
        $pp->playlist_id = $playlist->id;
        $pp->post_id = $request->post_id;
        $pp->position = $maxPos + 1;
        $pp->save();

        $playlist->increment('post_count');

        return GlobalFunction::sendSimpleResponse(true, 'Post added to playlist');
    }

    public function removePostFromPlaylist(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $playlist = Playlist::where('id', $request->playlist_id)
            ->where('user_id', $user->id)->first();
        if (!$playlist) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        $deleted = PlaylistPost::where('playlist_id', $playlist->id)
            ->where('post_id', $request->post_id)->delete();

        if ($deleted) {
            $playlist->decrement('post_count');
        }

        return GlobalFunction::sendSimpleResponse(true, 'Post removed from playlist');
    }

    public function fetchPlaylistPosts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'playlist_id' => 'required',
            'limit' => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $playlist = Playlist::find($request->playlist_id);
        if (!$playlist) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        // Non-owners can't see private playlists
        if (!$playlist->is_public && $playlist->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        $limit = $request->limit ?? 20;

        $query = PlaylistPost::where('playlist_id', $playlist->id)
            ->orderBy('position', 'asc');

        if ($request->last_item_id) {
            $lastItem = PlaylistPost::find($request->last_item_id);
            if ($lastItem) {
                $query->where('position', '>', $lastItem->position);
            }
        }

        $playlistPosts = $query->limit($limit)->get();
        $postIds = $playlistPosts->pluck('post_id')->toArray();

        if (empty($postIds)) {
            return GlobalFunction::sendDataResponse(true, 'posts fetched', []);
        }

        $posts = \App\Models\Post::with(Constants::postsWithArray)
            ->whereIn('id', $postIds)
            ->get();

        // Maintain playlist order
        $ordered = collect($postIds)->map(function ($id) use ($posts) {
            return $posts->firstWhere('id', $id);
        })->filter()->values();

        $ordered = GlobalFunction::processPostsListData($ordered, $user);

        return GlobalFunction::sendDataResponse(true, 'posts fetched', $ordered);
    }

    public function reorderPlaylistPosts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $playlist = Playlist::where('id', $request->playlist_id)
            ->where('user_id', $user->id)->first();
        if (!$playlist) {
            return GlobalFunction::sendSimpleResponse(false, 'Playlist not found');
        }

        // Expects post_ids as ordered array
        $postIds = $request->post_ids;
        if (!is_array($postIds)) {
            return GlobalFunction::sendSimpleResponse(false, 'post_ids must be an array');
        }

        foreach ($postIds as $index => $postId) {
            PlaylistPost::where('playlist_id', $playlist->id)
                ->where('post_id', $postId)
                ->update(['position' => $index]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Posts reordered');
    }
}
