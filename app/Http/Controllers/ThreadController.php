<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessUserNotificationJob;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Posts;
use App\Models\PostLikes;
use App\Models\PostSaves;
use App\Models\Followers;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ThreadController extends Controller
{
    /**
     * Create a new thread (first post becomes thread parent).
     * Accepts multiple text segments to create a thread in one request.
     */
    public function createThread(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'posts' => 'required|array|min:2|max:10',
            'posts.*.description' => 'required|string|max:2000',
            'can_comment' => 'nullable',
            'visibility' => 'nullable|integer|in:0,1,2,3',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $postsData = $request->posts;
        $createdPosts = [];

        // Create the first post as the thread parent
        $firstPostRequest = new Request([
            'description' => $postsData[0]['description'],
            'can_comment' => $request->can_comment ?? 1,
            'visibility' => $request->visibility ?? 0,
            'hashtags' => $postsData[0]['hashtags'] ?? null,
            'mentioned_user_ids' => $postsData[0]['mentioned_user_ids'] ?? null,
        ]);

        $firstPost = GlobalFunction::generatePost($firstPostRequest, Constants::postTypeText, $user, null);
        // Set thread_id to its own id (it's the parent)
        $firstPost->thread_id = $firstPost->id;
        $firstPost->thread_position = 0;
        $firstPost->save();

        $createdPosts[] = $firstPost;

        // Create subsequent thread posts
        for ($i = 1; $i < count($postsData); $i++) {
            $segmentRequest = new Request([
                'description' => $postsData[$i]['description'],
                'can_comment' => $request->can_comment ?? 1,
                'visibility' => $request->visibility ?? 0,
                'hashtags' => $postsData[$i]['hashtags'] ?? null,
                'mentioned_user_ids' => $postsData[$i]['mentioned_user_ids'] ?? null,
            ]);

            $post = GlobalFunction::generatePost($segmentRequest, Constants::postTypeText, $user, null);
            $post->thread_id = $firstPost->id;
            $post->thread_position = $i;
            $post->save();

            $createdPosts[] = $post;
        }

        // Process each post for feed data
        $userId = $user->id;
        foreach ($createdPosts as &$post) {
            $post->is_liked = false;
            $post->is_saved = false;
            $post->user = $user;
            $post->thread_count = count($createdPosts);
        }

        return GlobalFunction::sendDataResponse(true, 'Thread created successfully', [
            'thread_id' => $firstPost->id,
            'posts' => $createdPosts,
        ]);
    }

    /**
     * Add a new post to an existing thread.
     */
    public function addToThread(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'thread_id' => 'required|integer',
            'description' => 'required|string|max:2000',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Verify thread exists and belongs to user
        $threadParent = Posts::where('id', $request->thread_id)
            ->where('thread_id', $request->thread_id) // is the parent
            ->where('user_id', $user->id)
            ->first();

        if (!$threadParent) {
            return GlobalFunction::sendSimpleResponse(false, 'Thread not found or not yours');
        }

        // Get current max position
        $maxPosition = Posts::where('thread_id', $request->thread_id)->max('thread_position') ?? 0;

        if ($maxPosition >= 9) {
            return GlobalFunction::sendSimpleResponse(false, 'Thread is at maximum length (10 posts)');
        }

        $postRequest = new Request([
            'description' => $request->description,
            'can_comment' => $threadParent->can_comment,
            'visibility' => $threadParent->visibility,
            'hashtags' => $request->hashtags ?? null,
            'mentioned_user_ids' => $request->mentioned_user_ids ?? null,
        ]);

        $post = GlobalFunction::generatePost($postRequest, Constants::postTypeText, $user, null);
        $post->thread_id = $request->thread_id;
        $post->thread_position = $maxPosition + 1;
        $post->save();

        $post->is_liked = false;
        $post->is_saved = false;
        $post->user = $user;

        return GlobalFunction::sendDataResponse(true, 'Post added to thread', $post);
    }

    /**
     * Fetch all posts in a thread, ordered by position.
     */
    public function fetchThread(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = ['thread_id' => 'required|integer'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $posts = Posts::where('thread_id', $request->thread_id)
            ->orderBy('thread_position', 'asc')
            ->get();

        if ($posts->isEmpty()) {
            return GlobalFunction::sendSimpleResponse(false, 'Thread not found');
        }

        $userId = $user->id;
        $postIds = $posts->pluck('id')->toArray();
        $likedPostIds = PostLikes::where('user_id', $userId)->whereIn('post_id', $postIds)->pluck('post_id')->toArray();
        $savedPostIds = PostSaves::where('user_id', $userId)->whereIn('post_id', $postIds)->pluck('post_id')->toArray();

        foreach ($posts as $post) {
            $post->is_liked = in_array($post->id, $likedPostIds);
            $post->is_saved = in_array($post->id, $savedPostIds);
            $post->user = Users::select(explode(',', Constants::userPublicFields))->find($post->user_id);
            $post->mentioned_users = !empty($post->mentioned_user_ids)
                ? Users::whereIn('id', explode(',', $post->mentioned_user_ids))->select(explode(',', Constants::userPublicFields))->get()
                : [];
        }

        return GlobalFunction::sendDataResponse(true, 'Thread fetched', [
            'thread_id' => (int)$request->thread_id,
            'posts' => $posts,
        ]);
    }

    /**
     * Create a quote repost — a new text post that references another post.
     */
    public function quoteRepost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'quoted_post_id' => 'required|integer',
            'description' => 'nullable|string|max:2000',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $quotedPost = Posts::find($request->quoted_post_id);
        if (!$quotedPost) {
            return GlobalFunction::sendSimpleResponse(false, 'Original post not found');
        }

        $postRequest = new Request([
            'description' => $request->description ?? '',
            'can_comment' => 1,
            'visibility' => 0,
            'hashtags' => $request->hashtags ?? null,
            'mentioned_user_ids' => $request->mentioned_user_ids ?? null,
        ]);

        $post = GlobalFunction::generatePost($postRequest, Constants::postTypeText, $user, null);
        $post->is_quote_repost = true;
        $post->quoted_post_id = $request->quoted_post_id;
        $post->save();

        // Increment repost count on original post
        $quotedPost->increment('shares');

        // Load the quoted post data for the response
        $quotedPost->user = Users::select(explode(',', Constants::userPublicFields))->find($quotedPost->user_id);

        $post->is_liked = false;
        $post->is_saved = false;
        $post->user = $user;
        $post->quoted_post = $quotedPost;

        // Notify the original post owner
        if ($quotedPost->user_id != $user->id) {
            ProcessUserNotificationJob::dispatch(
                Constants::notify_repost,
                $user->id,
                $quotedPost->user_id,
                $post->id
            );
        }

        return GlobalFunction::sendDataResponse(true, 'Quote repost created', $post);
    }
}
