<?php

namespace App\Http\Controllers;

use App\Models\CommentReaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Hashtags;
use App\Models\Posts;
use App\Models\Repost;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SocialController extends Controller
{
    // ─── Repost ───────────────────────────────────────────────────

    /**
     * Repost a post to the user's profile
     */
    public function repostPost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['post_id' => 'required|exists:tbl_post,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if ($post->user_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'cannot repost your own post');
        }

        // Check if already reposted
        $existing = Repost::where('user_id', $user->id)
            ->where('original_post_id', $request->post_id)
            ->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'already reposted');
        }

        Repost::create([
            'user_id' => $user->id,
            'original_post_id' => $request->post_id,
            'caption' => $request->caption ?? null,
        ]);

        $post->increment('repost_count');

        return GlobalFunction::sendSimpleResponse(true, 'post reposted');
    }

    /**
     * Undo a repost
     */
    public function undoRepost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['post_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $repost = Repost::where('user_id', $user->id)
            ->where('original_post_id', $request->post_id)
            ->first();
        if (!$repost) {
            return GlobalFunction::sendSimpleResponse(false, 'repost not found');
        }

        $repost->delete();

        $post = Posts::find($request->post_id);
        if ($post && $post->repost_count > 0) {
            $post->decrement('repost_count');
        }

        return GlobalFunction::sendSimpleResponse(true, 'repost removed');
    }

    /**
     * Fetch reposts by a user (for profile "Reposts" tab)
     */
    public function fetchUserReposts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $targetUserId = $request->user_id ?? $user->id;
        $limit = $request->limit ?? 20;
        $lastItemId = $request->last_item_id;

        $query = Repost::where('user_id', $targetUserId)
            ->with(['originalPost' => function ($q) {
                $q->with(Constants::postsWithArray);
            }, 'user:id,username,fullname,profile_photo'])
            ->orderBy('id', 'DESC')
            ->limit($limit);

        if ($lastItemId) {
            $query->where('id', '<', $lastItemId);
        }

        $reposts = $query->get();

        // Extract original posts and process them
        $posts = $reposts->map(function ($repost) {
            $post = $repost->originalPost;
            if ($post) {
                $post->repost_by = [
                    'user_id' => $repost->user_id,
                    'username' => $repost->user->username ?? null,
                    'caption' => $repost->caption,
                    'repost_id' => $repost->id,
                    'reposted_at' => $repost->created_at,
                ];
            }
            return $post;
        })->filter();

        $processedPosts = GlobalFunction::processPostsListData($posts, $user);

        return GlobalFunction::sendDataResponse(true, 'reposts fetched', $processedPosts);
    }

    // ─── Trending Hashtags ───────────────────────────────────────

    /**
     * Fetch trending hashtags with post counts
     */
    public function fetchTrendingHashtags(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $limit = $request->limit ?? 30;

        $hashtags = Cache::remember("trending_hashtags:{$limit}", 300, function () use ($limit) {
            return Hashtags::where('post_count', '>=', 1)
                ->orderBy('post_count', 'DESC')
                ->limit($limit)
                ->get();
        });

        return GlobalFunction::sendDataResponse(true, 'trending hashtags fetched', $hashtags);
    }

    // ─── Online / Last Seen ──────────────────────────────────────

    /**
     * Fetch online status of a list of users (for chat screen)
     */
    public function fetchUsersOnlineStatus(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $userIds = $request->user_ids;
        if (empty($userIds)) {
            return GlobalFunction::sendDataResponse(true, 'no users', []);
        }

        if (is_string($userIds)) {
            $userIds = array_map('intval', explode(',', $userIds));
        }

        $users = Users::whereIn('id', $userIds)
            ->select('id', 'app_last_used_at')
            ->get()
            ->map(function ($u) {
                $lastUsed = $u->app_last_used_at ? Carbon::parse($u->app_last_used_at) : null;
                $isOnline = $lastUsed && $lastUsed->gt(Carbon::now()->subMinutes(5));
                return [
                    'user_id' => $u->id,
                    'is_online' => $isOnline,
                    'last_seen' => $lastUsed ? $lastUsed->toIso8601String() : null,
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'status fetched', $users);
    }

    // ─── Comment Reactions ──────────────────────────────────────

    /**
     * Add an emoji reaction to a comment
     */
    public function reactToComment(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
            'emoji' => 'required|string|max:10',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $existing = CommentReaction::where('user_id', $user->id)
            ->where('comment_id', $request->comment_id)
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            // Remove reaction (toggle off)
            $existing->delete();
            return GlobalFunction::sendSimpleResponse(true, 'reaction removed');
        }

        CommentReaction::create([
            'user_id' => $user->id,
            'comment_id' => $request->comment_id,
            'emoji' => $request->emoji,
        ]);

        return GlobalFunction::sendSimpleResponse(true, 'reaction added');
    }

    /**
     * Fetch reactions for a comment
     */
    public function fetchCommentReactions(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['comment_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $reactions = CommentReaction::getReactionCounts((int) $request->comment_id);
        $myReactions = CommentReaction::where('user_id', $user->id)
            ->where('comment_id', $request->comment_id)
            ->pluck('emoji')
            ->toArray();

        return GlobalFunction::sendDataResponse(true, 'reactions fetched', [
            'reactions' => $reactions,
            'my_reactions' => $myReactions,
        ]);
    }
}
