<?php

namespace App\Http\Controllers;

use App\Models\CommentLikes;
use App\Models\CommentReplies;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Jobs\ProcessUserNotificationJob;
use App\Models\PostComments;
use App\Models\PostLikes;
use App\Models\PostSaves;
use App\Models\Posts;
use App\Models\Users;
use Egulias\EmailValidator\Parser\Comment;
use Google\Service\Blogger\PostReplies;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Helpers\AnalyticsHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    //

    public function deleteComment_Admin(Request $request){
        $item = PostComments::find($request->id);
        $commentId = $item->id;
        $postId = $item->post_id;
        $item->delete();

        CommentReplies::where('comment_id', $commentId)->delete();
        GlobalFunction::settlePostCommentCount($postId);

        return GlobalFunction::sendSimpleResponse(true, 'comment deleted successfully');
    }

    public function deleteCommentReply_Admin(Request $request){
        $item = CommentReplies::find($request->id);
        $commentId = $item->comment_id;
        $item->delete();

        GlobalFunction::settleCommentsRepliesCount($commentId);
        return GlobalFunction::sendSimpleResponse(true, 'comment reply deleted successfully');
    }

    public function listCommentReplies(Request $request)
    {
        $query = CommentReplies::query();
        $query->where('comment_id', $request->commentId);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reply', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $delete = "<a href='#'
            rel='{$item->id}'
            class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
              <i class='uil-trash-alt'></i>
            </a>";

            $action = "<span class='d-flex justify-content-end align-items-center'>{$delete}</span>";

            $replyUser = GlobalFunction::createUserDetailsColumn($item->user_id);

            $formattedReply = GlobalFunction::formatDescription($item->reply);

            $reply = '<div class="itemDescription d-inline">'.$formattedReply.'</div>';

            return [
                $reply,
                $replyUser,
                GlobalFunction::formateDatabaseTime($item->created_at),
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }
    public function listPostComments(Request $request)
    {
        $query = PostComments::query();
        $query->where('post_id', $request->postId);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('comment', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $replies = "<a href='#'
            rel='{$item->id}'
            class='action-btn show-replies d-flex align-items-center justify-content-center btn border rounded-2 text-info ms-1'>
              <i class='uil-comments'></i>
            </a>";
            $delete = "<a href='#'
            rel='{$item->id}'
            class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
              <i class='uil-trash-alt'></i>
            </a>";

            $action = "<span class='d-flex justify-content-end align-items-center'>{$replies}{$delete}</span>";

            $commentUser = GlobalFunction::createUserDetailsColumn($item->user_id);

            $states = GlobalFunction::createCommentStatesView($item->id);

            if($item->type == Constants::commentTypeImage){
                $formattedComment = '<img class="rounded" width="80" height="80" src='.$item->comment.' alt="">';
            }else{
                $formattedComment = GlobalFunction::formatDescription($item->comment);
            }

            $commentAndStats = '<div class="itemDescription d-inline">'.$formattedComment.'</div><div class="mt-1">'.$states.'</div>';

            return [
                $commentAndStats,
                $commentUser,
                GlobalFunction::formateDatabaseTime($item->created_at),
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }

    public function deleteCommentReply(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'reply_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $reply = CommentReplies::find($request->reply_id);
        $commentId = $reply->comment_id;

        if($reply == null){
            return GlobalFunction::sendSimpleResponse(false, 'reply does not exists!');
        }
        $reply->delete();

        GlobalFunction::settleCommentsRepliesCount($commentId);
        GlobalFunction::deleteNotifications(Constants::notify_reply_comment, $reply->id, $user->id);

        return GlobalFunction::sendSimpleResponse(true, 'reply deleted successfully');

    }
    public function deleteComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $comment = PostComments::find($request->comment_id);
        $postId = $comment->post_id;

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }
        CommentReplies::where('comment_id', $comment->id)->delete();
        $comment->delete();

        GlobalFunction::settlePostCommentCount($postId);
        GlobalFunction::deleteNotifications(Constants::notify_comment_post,$comment->id,$user->id);

        return GlobalFunction::sendSimpleResponse(true, 'comment deleted successfully');

    }
    public function fetchPostCommentReplies(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $comment = PostComments::find($request->comment_id);

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }
        $query = CommentReplies::where('comment_id', $comment->id)
        ->orderBy('id', 'DESC')
        ->limit($request->limit)
        ->with(['user:'.Constants::userPublicFields]);
        if($request->has('last_item_id')){
            $query->where('id','<',$request->last_item_id);
        }
       $replies = $query ->get();

        if($replies->count() > 0){
            foreach($replies as $reply){
                $reply->mentionedUsers = Users::whereIn('id', explode(',', $reply->mentioned_user_ids))
                                        ->select(explode(',',Constants::userPublicFields))
                                        ->get();
            }
        }

        return GlobalFunction::sendDataResponse(true,'comment replies fetched successfully', $replies);

    }

    public function fetchPostComments(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $post = GlobalFunction::preparePostFullData($request->post_id);

        if($post == null){
            return GlobalFunction::sendSimpleResponse(false, 'post does not exists!');
        }
        $pinnedQuery = PostComments::where([
            'post_id'=> $post->id,
            'is_pinned'=> 1,
            'is_approved' => true,
        ])
        ->with(['user:'.Constants::userPublicFields]);
        $pinnedComments = $pinnedQuery->get();

        // Get restricted user IDs for the post owner
        $postOwnerId = $post->user_id;
        $restrictedIds = GlobalFunction::getUsersRestrictedUsersIdsArray($postOwnerId);

        $isPostOwner = $user->id == $postOwnerId;

        $query = PostComments::where('post_id', $post->id)
                    ->orderBy('id', 'DESC')
                    ->limit($request->limit)
                    ->with(['user:'.Constants::userPublicFields]);
                    if($request->has('last_item_id')){
                        $query->where('id','<',$request->last_item_id);
                    }
                    // Hide restricted users' comments unless the viewer is the restricted user themselves
                    if (!empty($restrictedIds)) {
                        $query->where(function ($q) use ($restrictedIds, $user) {
                            $q->whereNotIn('user_id', $restrictedIds)
                              ->orWhere('user_id', $user->id);
                        });
                    }
                    // Non-owners only see approved comments
                    if (!$isPostOwner) {
                        $query->where('is_approved', true);
                    }
                   $comments = $query ->get();

        // Filter comments containing viewer's hidden words
        $hiddenWords = GlobalFunction::getUserHiddenWordsArray($user->id);
        if (!empty($hiddenWords)) {
            $comments = $comments->filter(function ($comment) use ($hiddenWords, $user) {
                if ($comment->user_id == $user->id) return true;
                return !GlobalFunction::commentContainsHiddenWord($comment->comment, $hiddenWords);
            })->values();
        }

        // Like or not
        foreach($comments as $comment){
            $comment->is_liked = false;
            $like = CommentLikes::where('comment_id', $comment->id)->where('user_id', $user->id)->first();
            if($like){
                $comment->is_liked = true;
            }
            $comment->mentionedUsers = Users::whereIn('id', explode(',', $comment->mentioned_user_ids))
            ->select(explode(',',Constants::userPublicFields))
            ->get();

        }

        if($pinnedComments->count() > 0){
            foreach($pinnedComments as $comment){
                $comment->is_liked = false;
                $like = CommentLikes::where('comment_id', $comment->id)->where('user_id', $user->id)->first();
                if($like){
                    $comment->is_liked = true;
                }
            $comment->mentionedUsers = Users::whereIn('id', explode(',', $comment->mentioned_user_ids))
            ->select(explode(',',Constants::userPublicFields))
            ->get();
            }
        }
        // End : Like or not

        $data['comments'] = $comments;
        $data['pinnedComments'] = $pinnedComments;

        // Return pending comment count for post owner
        if ($isPostOwner) {
            $data['pending_count'] = PostComments::where('post_id', $post->id)
                ->where('is_approved', false)
                ->count();
        }

        return GlobalFunction::sendDataResponse(true,'comments data fetched successfully', $data);

    }

    public function unPinComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $comment = PostComments::find($request->comment_id);

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }
        $comment->is_pinned = 0; // 1=pinned 0=not
        $comment->save();

        return GlobalFunction::sendSimpleResponse(true, 'comment un-pinned successfull');

    }
    public function pinComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $comment = PostComments::find($request->comment_id);
        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }

        $settings = GlobalSettings::getCached();
        $pinnedCommentsCounts = PostComments::where([
            'post_id'=> $comment->post_id,
            'is_pinned'=> 1,
        ])->count();
        if($pinnedCommentsCounts >= $settings->max_comment_pins){
            return GlobalFunction::sendSimpleResponse(false, 'you can only pin only '.$settings->max_comment_pins.' comments for each post!');
        }


        $comment->is_pinned = 1; // 1=pinned 0=not
        $comment->save();

        return GlobalFunction::sendSimpleResponse(true, 'comment pinned successfull');

    }

    public function replyToComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
            'reply' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $comment = PostComments::find($request->comment_id);

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }
        // Block check
        $post = Posts::find($comment->post_id);
        if ($post) {
            $isBlock = GlobalFunction::checkUserBlock($user->id, $post->user_id);
            if ($isBlock) {
                return GlobalFunction::sendSimpleResponse(false, 'you can not reply on this post!');
            }
        }
         // Checking Daily Limit of reply
         $globalSettings = GlobalSettings::getCached();
         $commentCount = CommentReplies::where('user_id', $user->id)->whereDate('created_at', Carbon::today())->count();
         if ($commentCount >= $globalSettings->max_comment_reply_daily) {
             return ['status' => false, 'message' => "daily comment-reply limit exhausted!"];
         }
         $reply = new CommentReplies();
         $reply->user_id = $user->id;
         $reply->comment_id = $comment->id;
         $reply->reply = $request->reply;
         if ($request->has('mentioned_user_ids')) {
            $reply->mentioned_user_ids = GlobalFunction::cleanString($request->mentioned_user_ids);
        }
         $reply->save();
         // Insert Notification Data : Mention In Reply Of Comment
        if ($request->has('mentioned_user_ids')) {
            $mentionedUsers = Users::whereIn('id', explode(',',$reply->mentioned_user_ids))->get();
            foreach($mentionedUsers as $mUser){
                ProcessUserNotificationJob::dispatch(Constants::notify_mention_reply, $user->id, $mUser->id, $reply->id);
            }
        }

         $reply = CommentReplies::where('id', $reply->id)->with(['user:'.Constants::userPublicFields])->first();

         GlobalFunction::settleCommentsRepliesCount($comment->id);

         // Insert Notification Data : Reply Added
        ProcessUserNotificationJob::dispatch(Constants::notify_reply_comment, $user->id, $comment->user_id, $reply->id);

        $replyPost = Posts::find($comment->post_id);
        AnalyticsHelper::publishEvent('comment', $user->id, ['postId' => $comment->post_id, 'postType' => $replyPost->post_type ?? null, 'targetUserId' => $comment->user_id]);

         return GlobalFunction::sendDataResponse(true, 'reply added successfully', $reply);


    }

    public function disLikeComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $comment = PostComments::find($request->comment_id);

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }
        $like = CommentLikes::where([
            'user_id'=> $user->id,
            'comment_id'=> $comment->id
        ])->first();

        if($like == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment like does not exists!');
        }
        $like->delete();

        GlobalFunction::settleCommentsLikesCount($comment->id);

        return GlobalFunction::sendSimpleResponse(true, 'comment disliked successfully');
    }
    public function fetchCommentByReplyId(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'reply_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $reply = CommentReplies::find($request->reply_id);

        if($reply == null){
            return GlobalFunction::sendSimpleResponse(false, 'reply does not exists!');
        }

        $comment = PostComments::where('id',$reply->comment_id)
                    ->with(['user:'.Constants::userPublicFields])
                    ->first();

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }

        $comment->is_liked = false;
        $like = CommentLikes::where('comment_id', $comment->id)->where('user_id', $user->id)->first();
        if($like){
            $comment->is_liked = true;
        }

        $comment->mentionedUsers = Users::whereIn('id', explode(',', $comment->mentioned_user_ids))
        ->select(explode(',',Constants::userPublicFields))
        ->get();

        return GlobalFunction::sendDataResponse(true, 'comment fetched successfully', $comment);
    }
    public function fetchCommentById(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $comment = PostComments::where('id',$request->comment_id)
                    ->with(['user:'.Constants::userPublicFields])
                    ->first();

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }

        $comment->is_liked = false;
        $like = CommentLikes::where('comment_id', $comment->id)->where('user_id', $user->id)->first();
        if($like){
            $comment->is_liked = true;
        }

        $comment->mentionedUsers = Users::whereIn('id', explode(',', $comment->mentioned_user_ids))
        ->select(explode(',',Constants::userPublicFields))
        ->get();

        return GlobalFunction::sendDataResponse(true, 'comment fetched successfully', $comment);
    }
    public function likeComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'comment_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $comment = PostComments::find($request->comment_id);

        if($comment == null){
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exists!');
        }
        $like = CommentLikes::where([
            'user_id'=> $user->id,
            'comment_id'=> $comment->id
        ])->first();
        if($like != null){
            return GlobalFunction::sendSimpleResponse(false, 'comment is liked already!');
        }
        $like = new CommentLikes();
        $like->comment_id = $comment->id;
        $like->user_id = $user->id;
        $like->save();

        GlobalFunction::settleCommentsLikesCount($comment->id);

        return GlobalFunction::sendSimpleResponse(true, 'comment liked successfully');
    }

    public function addPostComment(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
            'comment' => 'required',
            'type' => Rule::in([
                Constants::commentTypeText,
                Constants::commentTypeImage
            ]),
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $post = Posts::find($request->post_id);
        if($post == null){
            return GlobalFunction::sendSimpleResponse(false, 'post does not exists!');
        }
        // Block check
        $isBlock = GlobalFunction::checkUserBlock($user->id, $post->user_id);
        if ($isBlock) {
            return GlobalFunction::sendSimpleResponse(false, 'you can not comment on this post!');
        }
        // Checking Daily Limit
        $globalSettings = GlobalSettings::getCached();
        $commentCount = PostComments::where('user_id', $user->id)->whereDate('created_at', Carbon::today())->count();
        if ($commentCount >= $globalSettings->max_comment_daily) {
            return ['status' => false, 'message' => "daily comment limit exhausted!"];
        }
        // Add Comment
        $commentItem = new PostComments();
        $commentItem->user_id = $user->id;
        $commentItem->post_id = $post->id;
        $commentItem->type = $request->type;
        $commentItem->comment = $request->comment;
        if ($request->has('mentioned_user_ids')) {
            $commentItem->mentioned_user_ids = GlobalFunction::cleanString($request->mentioned_user_ids);
        }

        // Check if post owner has comment approval enabled
        $postOwner = Users::find($post->user_id);
        if ($postOwner && $postOwner->comment_approval_enabled && $user->id != $post->user_id) {
            $commentItem->is_approved = false;
        }

        $commentItem->save();
        GlobalFunction::settlePostCommentCount($post->id);

        // Only send notifications for approved comments
        if ($commentItem->is_approved) {
            // Insert Notification Data : Mention In Comment
            if ($request->has('mentioned_user_ids')) {
                $mentionedUsers = Users::whereIn('id', explode(',',$commentItem->mentioned_user_ids))->get();
                foreach($mentionedUsers as $mUser){
                    ProcessUserNotificationJob::dispatch(Constants::notify_mention_comment, $user->id, $mUser->id, $commentItem->id);
                }
            }

            // Insert Notification Data : Comment
            ProcessUserNotificationJob::dispatch(Constants::notify_comment_post, $user->id, $post->user_id, $commentItem->id);
        }

        AnalyticsHelper::publishEvent('comment', $user->id, ['postId' => $post->id, 'postType' => $post->post_type, 'targetUserId' => $post->user_id]);

        $comment = PostComments::where('id', $commentItem->id)->with(['user:'.Constants::userPublicFields])->first();
        return GlobalFunction::sendDataResponse(true, 'comment added successfully', $comment);
    }

    public function fetchVideoRepliesForComment(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = [
            'comment_id' => 'required',
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $query = Posts::where('reply_to_comment_id', $request->comment_id)
            ->with(Constants::postsWithArray)
            ->orderBy('id', 'DESC')
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $posts = $query->get();

        foreach ($posts as $post) {
            $post->is_liked = PostLikes::where('post_id', $post->id)->where('user_id', $user->id)->exists();
            $post->is_saved = PostSaves::where('post_id', $post->id)->where('user_id', $user->id)->exists();
            $post->mentioned_users = Users::whereIn('id', explode(',', $post->mentioned_user_ids))->get();
        }

        return GlobalFunction::sendDataResponse(true, 'video replies fetched successfully', $posts);
    }

    public function fetchPendingComments(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['post_id' => 'required', 'limit' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if ($post == null) {
            return GlobalFunction::sendSimpleResponse(false, 'post does not exist!');
        }

        // Only post owner can see pending comments
        if ($user->id != $post->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'unauthorized');
        }

        $query = PostComments::where('post_id', $post->id)
            ->where('is_approved', false)
            ->orderBy('id', 'DESC')
            ->limit($request->limit)
            ->with(['user:'.Constants::userPublicFields]);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $comments = $query->get();

        foreach ($comments as $comment) {
            $comment->mentionedUsers = Users::whereIn('id', explode(',', $comment->mentioned_user_ids))
                ->select(explode(',', Constants::userPublicFields))
                ->get();
        }

        return GlobalFunction::sendDataResponse(true, 'pending comments fetched', $comments);
    }

    public function approveComment(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['comment_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $comment = PostComments::find($request->comment_id);
        if ($comment == null) {
            return GlobalFunction::sendSimpleResponse(false, 'comment not found');
        }

        $post = Posts::find($comment->post_id);
        if ($post == null || $user->id != $post->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'unauthorized');
        }

        $comment->is_approved = true;
        $comment->save();

        // Now send the notification that was deferred
        ProcessUserNotificationJob::dispatch(Constants::notify_comment_post, $comment->user_id, $post->user_id, $comment->id);

        return GlobalFunction::sendSimpleResponse(true, 'comment approved');
    }

    public function creatorLikeComment(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['comment_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $comment = PostComments::find($request->comment_id);
        if ($comment == null) {
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exist!');
        }

        $post = Posts::find($comment->post_id);
        if ($post == null || $user->id != $post->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'only the post creator can creator-like a comment');
        }

        if ($comment->is_creator_liked) {
            return GlobalFunction::sendSimpleResponse(false, 'comment is already creator-liked');
        }

        $comment->is_creator_liked = true;
        $comment->save();

        // Recalculate score
        GlobalFunction::recalculateCommentScore($comment->id);

        // Send notification to comment author
        if ($user->id != $comment->user_id) {
            ProcessUserNotificationJob::dispatch(Constants::notify_creator_liked_comment, $user->id, $comment->user_id, $comment->id);
        }

        return GlobalFunction::sendSimpleResponse(true, 'comment creator-liked successfully');
    }

    public function creatorUnlikeComment(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['comment_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $comment = PostComments::find($request->comment_id);
        if ($comment == null) {
            return GlobalFunction::sendSimpleResponse(false, 'comment does not exist!');
        }

        $post = Posts::find($comment->post_id);
        if ($post == null || $user->id != $post->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'only the post creator can remove creator-like');
        }

        $comment->is_creator_liked = false;
        $comment->save();

        GlobalFunction::recalculateCommentScore($comment->id);

        return GlobalFunction::sendSimpleResponse(true, 'comment creator-like removed successfully');
    }

    public function fetchTopComments(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['post_id' => 'required', 'limit' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if ($post == null) {
            return GlobalFunction::sendSimpleResponse(false, 'post does not exist!');
        }

        // Get restricted user IDs for the post owner
        $restrictedIds = GlobalFunction::getUsersRestrictedUsersIdsArray($post->user_id);

        $query = PostComments::where('post_id', $post->id)
            ->where('is_approved', true)
            ->where('is_pinned', 0)
            ->orderBy('score', 'DESC')
            ->limit($request->limit)
            ->with(['user:'.Constants::userPublicFields]);

        if ($request->has('last_item_id')) {
            $lastComment = PostComments::find($request->last_item_id);
            if ($lastComment) {
                $query->where(function ($q) use ($lastComment) {
                    $q->where('score', '<', $lastComment->score)
                      ->orWhere(function ($q2) use ($lastComment) {
                          $q2->where('score', $lastComment->score)
                             ->where('id', '<', $lastComment->id);
                      });
                });
            }
        }

        if (!empty($restrictedIds)) {
            $query->where(function ($q) use ($restrictedIds, $user) {
                $q->whereNotIn('user_id', $restrictedIds)
                  ->orWhere('user_id', $user->id);
            });
        }

        $comments = $query->get();

        // Filter hidden words
        $hiddenWords = GlobalFunction::getUserHiddenWordsArray($user->id);
        if (!empty($hiddenWords)) {
            $comments = $comments->filter(function ($comment) use ($hiddenWords, $user) {
                if ($comment->user_id == $user->id) return true;
                return !GlobalFunction::commentContainsHiddenWord($comment->comment, $hiddenWords);
            })->values();
        }

        // Attach like status and mentioned users
        foreach ($comments as $comment) {
            $comment->is_liked = CommentLikes::where('comment_id', $comment->id)->where('user_id', $user->id)->exists();
            $comment->mentionedUsers = Users::whereIn('id', explode(',', $comment->mentioned_user_ids))
                ->select(explode(',', Constants::userPublicFields))
                ->get();
        }

        return GlobalFunction::sendDataResponse(true, 'top comments fetched successfully', $comments);
    }

    public function rejectComment(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['comment_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $comment = PostComments::find($request->comment_id);
        if ($comment == null) {
            return GlobalFunction::sendSimpleResponse(false, 'comment not found');
        }

        $post = Posts::find($comment->post_id);
        if ($post == null || $user->id != $post->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'unauthorized');
        }

        $comment->delete();
        GlobalFunction::settlePostCommentCount($post->id);

        return GlobalFunction::sendSimpleResponse(true, 'comment rejected');
    }

}
