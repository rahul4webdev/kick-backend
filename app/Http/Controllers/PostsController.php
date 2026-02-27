<?php

namespace App\Http\Controllers;

use App\Jobs\DeletePostDataJob;
use App\Jobs\ProcessUserNotificationJob;
use App\Models\CommentLikes;
use App\Models\CommentReplies;
use App\Models\Constants;
use App\Models\Followers;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Hashtags;
use App\Models\Musics;
use App\Models\PostComments;
use App\Models\PostLikes;
use App\Models\Posts;
use App\Models\PostSaves;
use App\Models\Users;
use App\Models\ContentGenre;
use App\Models\PostCollaborator;
use App\Models\Collection;
use App\Models\CollectionMember;
use App\Models\ContentLanguage;
use App\Models\NotInterested;
use App\Models\CreatorSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Helpers\AnalyticsHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PostsController extends Controller
{
    //
    public function postDetails($postId){
        $post = GlobalFunction::preparePostFullData($postId);

        $music = null;
        if($post->sound_id != null){
            $music = $post->music;
        }
        $formattedDesc = GlobalFunction::formatDescription($post->description);
        $states = GlobalFunction::createPostStatesView($postId);
        $postType = GlobalFunction::createPostTypeBadge($postId);
        $postUser = GlobalFunction::prepareUserFullData($post->user_id);
        $baseUrl = GlobalFunction::getItemBaseUrl();

        return view('postDetails',[
            'post'=> $post,
            'music'=> $music,
            'formattedDesc'=> $formattedDesc,
            'states'=> $states,
            'postType'=> $postType,
            'baseUrl'=> $baseUrl,
            'postUser'=> $postUser,
        ]);
    }
    public function fetchFormattedPostDesc(Request $request){
        $post = Posts::find($request->postId);
        $formattedDesc = GlobalFunction::formatDescription($post->description);

        return GlobalFunction::sendDataResponse(true,'Description fetched', $formattedDesc);

    }
    public function listHashtagPosts(Request $request)
    {
        $hashtag = $request->hashtag;
        $query = Posts::query();
        $query->whereRaw("? = ANY(string_to_array(hashtags, ','))", [$hashtag]);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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
    public function listUserPosts(Request $request)
    {
        $query = Posts::query();
        $query->where('user_id', $request->user_id);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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
    public function listAllPosts(Request $request)
    {
        $query = Posts::query();
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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
    public function listImagePosts(Request $request)
    {
        $query = Posts::query();
        $query->where('post_type', Constants::postTypeImage);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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
    public function listTextPosts(Request $request)
    {
        $query = Posts::query();
        $query->where('post_type', Constants::postTypeText);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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
    public function listVideoPosts(Request $request)
    {
        $query = Posts::query();
        $query->where('post_type', Constants::postTypeVideo);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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
    public function listReelPosts(Request $request)
    {
        $query = Posts::query();
        $query->where('post_type', Constants::postTypeReel);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {

            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";

            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);

            $states = GlobalFunction::createPostStatesView($post->id);

            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $formattedDesc = GlobalFunction::formatDescription($post->description);

            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            // View Content Button
            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
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

    public function posts(){
        return view('posts');
    }
    public function deletePost_Admin(Request $request){

        $post = Posts::find($request->id);
        $post->delete();

        // Async cascade delete via queue
        DeletePostDataJob::dispatch($post->id, $post->user_id, $post->hashtags, $post->sound_id);

        return GlobalFunction::sendSimpleResponse(true, 'post deleted successfully!');

    }
    public function deletePost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required|exists:tbl_post,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' =>  $validator->errors()->first()]);
        }
        $post = Posts::find($request->post_id);
        $post->delete();

        // Async cascade delete via queue
        DeletePostDataJob::dispatch($post->id, $post->user_id, $post->hashtags, $post->sound_id);

        return GlobalFunction::sendSimpleResponse(true, 'post deleted successfully!');

    }

    public function searchPosts(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $rules = [
            'types' => 'required',
            'limit' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

       $search = GlobalFunction::cleanString($request->keyword);

       $query = Posts::whereHas('user', function ($query) {
               $query->Where('is_freez', 0);
           })
            ->with(Constants::postsWithArray)
           ->whereNotIn('user_id', $blockedUserIds)
           ->whereIn('post_type', explode(',',$request->types))
           ->where('description', 'LIKE', "%{$search}%")
           ->orderBy('id', 'DESC')
           ->limit($request->limit);

           if($request->has('last_item_id')){
               $query->where('id','<',$request->last_item_id);
           }

       $posts = $query->get();

       // Track search for insights (only first page, non-empty keyword)
       if (!$request->has('last_item_id') && !empty($search)) {
           try {
               \App\Models\SearchHistory::create([
                   'user_id' => $user->id,
                   'keyword' => mb_substr($search, 0, 255),
                   'search_type' => 'posts',
                   'result_count' => $posts->count(),
                   'created_at' => now(),
               ]);
           } catch (\Exception $e) {}
       }

       $postList = GlobalFunction::processPostsListData($posts, $user);

       return GlobalFunction::sendDataResponse(true, 'search posts fetched successfully', $postList);

    }
    public function fetchSavedPosts(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'types' => 'required',
            'limit' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

       $query = PostSaves::whereHas('user', function ($query) {
               $query->Where('is_freez', 0);
           })->whereHas('post', function ($query) use ($request) {
            $query->whereIn('post_type', explode(',',$request->types));
        })
           ->with(['post.images','post.music','post.user:'.Constants::userPublicFields])
           ->orderBy('id', 'DESC')
           ->where('user_id', $user->id)
           ->limit($request->limit);

           if($request->has('last_item_id')){
               $query->where('id','<',$request->last_item_id);
           }

       $postSaves = $query->get();

       $post_list = [];
       foreach($postSaves as $save){

        $post = $save['post'];
        $post->post_save_id = $save->id;
        array_push($post_list, $post);
       }

       $postList = GlobalFunction::processPostsListData($post_list, $user);

       return GlobalFunction::sendDataResponse(true, 'saved posts fetched successfully', $postList);

    }
    public function fetchUserPosts(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
            'user_id'=>'required|exists:tbl_users,id',
            'types' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }
        $dataUser = Users::find($request->user_id);

        $pinnedPosts = Posts::with(Constants::postsWithArray)
        ->whereIn('post_type', explode(',',$request->types))
        ->where('user_id', $dataUser->id)
        ->where('is_pinned', 1)
        ->get();

        $pinnedPostList = GlobalFunction::processPostsListData($pinnedPosts, $user);


       // Get collaborative post IDs (accepted) for this user
       $collabPostIds = \App\Models\PostCollaborator::where('user_id', $dataUser->id)
           ->where('status', \App\Models\PostCollaborator::STATUS_ACCEPTED)
           ->pluck('post_id')
           ->toArray();

       $query = Posts::with(Constants::postsWithArray)
           ->whereIn('post_type', explode(',',$request->types))
           ->where(function ($q) use ($dataUser, $collabPostIds) {
               $q->where('user_id', $dataUser->id)
                 ->orWhereIn('id', $collabPostIds);
           })
           ->orderBy('id', 'DESC')
           ->limit($request->limit);

           if($request->has('last_item_id')){
               $query->where('id','<',$request->last_item_id);
           }

       $posts = $query->get();

       $postList = GlobalFunction::processPostsListData($posts, $user);

       $data['posts'] = $postList;
       $data['pinnedPostList'] = $pinnedPostList;

       return GlobalFunction::sendDataResponse(true, 'data fetched successfully', $data);

    }
    public function fetchPostsByHashtag(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
            'hashtag' => 'required',
            'types' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

       $hashtag = $request->hashtag;
        $query = Posts::whereHas('user', function ($query) {
                $query->Where('is_freez', 0);
            })
            ->whereNotIn('user_id', $blockedUserIds)
             ->with(Constants::postsWithArray)
            ->whereIn('post_type', explode(',',$request->types))
            ->whereRaw("? = ANY(string_to_array(hashtags, ','))", [$hashtag])
            ->orderBy('id', 'DESC')
            ->limit($request->limit);

            if($request->has('last_item_id')){
                $query->where('id','<',$request->last_item_id);
            }

        $posts = $query->get();

        $postList = GlobalFunction::processPostsListData($posts, $user);

        $hashtag = Hashtags::where('hashtag', $request->hashtag)->first();
        if ($hashtag) {
            $hashtagText = $hashtag->hashtag;
            $hashtag->post_count = Posts::whereRaw("? = ANY(string_to_array(hashtags, ','))", [$hashtagText])->count();
            $hashtag->save();
        }

        $data['hashtag'] = $hashtag;
        $data['posts'] = $postList;

        return GlobalFunction::sendDataResponse(true,'posts by hashtag fetched successfully', $data);
    }

    public function fetchReelPostsByMusic(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
            'music_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

        $query = Posts::whereHas('user', function ($query) {
                $query->Where('is_freez', 0);
            })
             ->with(Constants::postsWithArray)
            ->whereNotIn('user_id', $blockedUserIds)
            ->whereIn('post_type', [Constants::postTypeReel])
            ->where('sound_id', $request->music_id)
            ->orderBy('id', 'DESC')
            ->limit($request->limit);

            if($request->has('last_item_id')){
                $query->where('id','<',$request->last_item_id);
            }


        $posts = $query->get();

        $postList = GlobalFunction::processPostsListData($posts, $user);

        return GlobalFunction::sendDataResponse(true,'posts by music fetched successfully', $postList);

    }
    public function fetchExplorePageData(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

       $hashtags = Hashtags::where('post_count','>=',1)->orderBy('post_count','DESC')->get();

       $highPostHashtags = Hashtags::where('post_count','>=',4)->inRandomOrder()->get();

       foreach($highPostHashtags as $singleHashtag){

            $hashtag = $singleHashtag->hashtag;
            $posts = Posts::whereHas('user', function ($query) {
                $query->Where('is_freez', 0);
            })
            ->whereNotIn('user_id', $blockedUserIds)
             ->with(Constants::postsWithArray)
            ->whereIn('post_type', [Constants::postTypeImage,Constants::postTypeReel,Constants::postTypeVideo])
            ->whereRaw("? = ANY(string_to_array(hashtags, ','))", [$hashtag])
            ->inRandomOrder()
            ->limit(6)
            ->get();

            $postList = GlobalFunction::processPostsListData($posts, $user);
            $singleHashtag->postList = $postList;
       }

       $data['hashtags'] = $hashtags;
       $data['highPostHashtags'] = $highPostHashtags;

        return GlobalFunction::sendDataResponse(true,'explore data fetched successfully', $data);

    }
    public function fetchPostsDiscover(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
            'types' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
       $notInterestedPostIds = GlobalFunction::getUsersNotInterestedPostIdsArray($user->id);
       $mutedUserIds = GlobalFunction::getUsersMutedUsersIdsArray($user->id, 'posts');

       $limit = (int) $request->limit;
       $offset = (int) ($request->offset ?? 0);

       // Try personalized recommendations from kickanalytics
       $rankedPostIds = $this->getRecommendedPostIds($user->id, $limit, $offset);

       if (!empty($rankedPostIds)) {
           // Fetch posts in ranked order
           $posts = Posts::whereIn('id', $rankedPostIds)
               ->whereHas('user', function ($query) {
                   $query->where('is_freez', 0);
               })
               ->with(Constants::postsWithArray)
               ->whereNotIn('user_id', $blockedUserIds)
               ->whereNotIn('user_id', $mutedUserIds)
               ->whereNotIn('id', $notInterestedPostIds)
               ->whereIn('post_type', explode(',', $request->types))
               ->where('content_type', Constants::contentTypeNormal)
               ->where('visibility', Constants::postVisibilityPublic)
               ->where('post_status', Constants::postStatusPublished)
               ->orderByRaw("array_position(ARRAY[" . implode(',', $rankedPostIds) . "]::int[], id)")
               ->get();
       } else {
           // Fallback to random (cold start or service unavailable)
           $posts = Posts::inRandomOrder()
               ->whereHas('user', function ($query) {
                   $query->where('is_freez', 0);
               })
               ->with(Constants::postsWithArray)
               ->whereNotIn('user_id', $blockedUserIds)
               ->whereNotIn('user_id', $mutedUserIds)
               ->whereNotIn('id', $notInterestedPostIds)
               ->whereIn('post_type', explode(',', $request->types))
               ->where('content_type', Constants::contentTypeNormal)
               ->where('visibility', Constants::postVisibilityPublic)
               ->where('post_status', Constants::postStatusPublished)
               ->limit($limit)
               ->get();
       }

        $postList = GlobalFunction::processPostsListData($posts, $user);
        $adPositions = GlobalFunction::computeAdPositions(count($postList));

        return GlobalFunction::sendFeedResponse(true, 'discover posts fetched successfully', $postList, $adPositions);

    }
    public function fetchPostsNearBy(Request $request)
        {
            $token = $request->header('authtoken');
            $user = GlobalFunction::getUserFromAuthToken($token);

            if ($user->is_freez == 1) {
                return ['status' => false, 'message' => "this user is freezed!"];
            }

            $rules = [
                'place_lat' => 'required',
                'place_lon' => 'required',
                'types' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $latitude = $request->place_lat;
            $longitude = $request->place_lon;
            $radius = 5; // range in KM

            $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
            $notInterestedPostIds = GlobalFunction::getUsersNotInterestedPostIdsArray($user->id);
            $mutedUserIds = GlobalFunction::getUsersMutedUsersIdsArray($user->id, 'posts');

            $query = Posts::whereHas('user', function ($query) {
                    $query->where('is_freez', 0);
                })
                ->whereNotIn('user_id', $blockedUserIds)
                ->whereNotIn('user_id', $mutedUserIds)
                ->whereNotIn('id', $notInterestedPostIds)
                 ->with(Constants::postsWithArray)
                ->whereIn('post_type', explode(',', $request->types))
                ->where('content_type', Constants::contentTypeNormal)
                ->where('visibility', Constants::postVisibilityPublic)
                ->where('post_status', Constants::postStatusPublished)
                ->whereRaw("(6371 * acos(cos(radians(?))
                    * cos(radians(place_lat))
                    * cos(radians(place_lon) - radians(?))
                    + sin(radians(?))
                    * sin(radians(place_lat)))) <= ?",
                    [$latitude, $longitude, $latitude, $radius]
                )
                ->inRandomOrder()
                ->limit(50);

            $posts = $query->get();

            $postList = GlobalFunction::processPostsListData($posts, $user);
            $adPositions = GlobalFunction::computeAdPositions(count($postList));

            return GlobalFunction::sendFeedResponse(true, 'Nearby posts fetched successfully', $postList, $adPositions);
        }
    public function fetchTrendingPosts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'types' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $scope = $request->get('scope', 'global');
        $limit = (int) $request->get('limit', 20);
        $offset = (int) $request->get('offset', 0);

        $key = 'trending:' . $scope;
        $postIds = Redis::zrevrange($key, $offset, $offset + $limit - 1);

        if (empty($postIds)) {
            return GlobalFunction::sendDataResponse(true, 'trending posts', []);
        }

        $postIds = array_map('intval', $postIds);
        $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
        $notInterestedPostIds = GlobalFunction::getUsersNotInterestedPostIdsArray($user->id);
        $mutedUserIds = GlobalFunction::getUsersMutedUsersIdsArray($user->id, 'posts');

        $posts = Posts::whereIn('id', $postIds)
            ->whereHas('user', function ($query) {
                $query->where('is_freez', 0);
            })
            ->with(Constants::postsWithArray)
            ->whereNotIn('user_id', $blockedUserIds)
            ->whereNotIn('user_id', $mutedUserIds)
            ->whereNotIn('id', $notInterestedPostIds)
            ->whereIn('post_type', explode(',', $request->types))
            ->where('content_type', Constants::contentTypeNormal)
            ->where('visibility', Constants::postVisibilityPublic)
            ->where('post_status', Constants::postStatusPublished)
            ->orderByRaw("array_position(ARRAY[" . implode(',', $postIds) . "]::int[], id)")
            ->get();

        $postList = GlobalFunction::processPostsListData($posts, $user);
        $adPositions = GlobalFunction::computeAdPositions(count($postList));

        return GlobalFunction::sendFeedResponse(true, 'trending posts fetched successfully', $postList, $adPositions);
    }

    public function fetchPostsByLocation(Request $request)
        {
            $token = $request->header('authtoken');
            $user = GlobalFunction::getUserFromAuthToken($token);

            if ($user->is_freez == 1) {
                return ['status' => false, 'message' => "this user is freezed!"];
            }

            $rules = [
                'place_lat' => 'required',
                'place_lon' => 'required',
                'limit' => 'required|integer',
                'types' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $latitude = $request->place_lat;
            $longitude = $request->place_lon;
            $radius = 1; // range in KM

            $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

            $query = Posts::whereHas('user', function ($query) {
                    $query->where('is_freez', 0);
                })
                ->whereNotIn('user_id', $blockedUserIds)
                 ->with(Constants::postsWithArray)
                ->whereIn('post_type', explode(',', $request->types))
                ->where('visibility', Constants::postVisibilityPublic)
                ->whereRaw("(6371 * acos(cos(radians(?))
                    * cos(radians(place_lat))
                    * cos(radians(place_lon) - radians(?))
                    + sin(radians(?))
                    * sin(radians(place_lat)))) <= ?",
                    [$latitude, $longitude, $latitude, $radius]
                )
                ->orderBy('id', 'DESC')
                ->limit($request->limit);

            if ($request->has('last_item_id')) {
                $query->where('id', '<', $request->last_item_id);
            }

            $posts = $query->get();

            $postList = GlobalFunction::processPostsListData($posts, $user);

            return GlobalFunction::sendDataResponse(true, 'posts by location fetched successfully', $postList);
        }

    public function fetchPostsFollowing(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
            'types' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
       $notInterestedPostIds = GlobalFunction::getUsersNotInterestedPostIdsArray($user->id);
       $mutedUserIds = GlobalFunction::getUsersMutedUsersIdsArray($user->id, 'posts');
       $followingUserIds = Followers::where('from_user_id', $user->id)->pluck('to_user_id')->toArray();

       // Get collaborative post IDs where a followed user is a collaborator
       $collabPostIds = PostCollaborator::whereIn('user_id', $followingUserIds)
           ->where('status', PostCollaborator::STATUS_ACCEPTED)
           ->pluck('post_id')
           ->toArray();

        $posts = Posts::inRandomOrder()
            ->whereHas('user', function ($query) {
                $query->Where('is_freez', 0);
            })
            ->with(Constants::postsWithArray)
            ->whereNotIn('user_id', $blockedUserIds)
            ->whereNotIn('user_id', $mutedUserIds)
            ->whereNotIn('id', $notInterestedPostIds)
            ->where(function ($q) use ($followingUserIds, $collabPostIds) {
                $q->whereIn('user_id', $followingUserIds)
                  ->orWhereIn('id', $collabPostIds);
            })
            ->whereIn('post_type', explode(',',$request->types))
            ->where('content_type', Constants::contentTypeNormal)
            ->where('visibility', '!=', Constants::postVisibilityOnlyMe)
            ->where('post_status', Constants::postStatusPublished)
            ->limit($request->limit)
            ->get();

            $postList = GlobalFunction::processPostsListData($posts, $user);
            $adPositions = GlobalFunction::computeAdPositions(count($postList));

        return GlobalFunction::sendFeedResponse(true,'following posts fetched successfully', $postList, $adPositions);

    }

    public function fetchPostsFavorites(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
            'types' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

       $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
       $notInterestedPostIds = GlobalFunction::getUsersNotInterestedPostIdsArray($user->id);
       $mutedUserIds = GlobalFunction::getUsersMutedUsersIdsArray($user->id, 'posts');
       $favoriteUserIds = GlobalFunction::getUsersFavoriteUserIdsArray($user->id);

        $posts = Posts::inRandomOrder()
            ->whereHas('user', function ($query) {
                $query->Where('is_freez', 0);
            })
            ->with(Constants::postsWithArray)
            ->whereNotIn('user_id', $blockedUserIds)
            ->whereNotIn('user_id', $mutedUserIds)
            ->whereNotIn('id', $notInterestedPostIds)
            ->whereIn('user_id', $favoriteUserIds)
            ->whereIn('post_type', explode(',',$request->types))
            ->where('content_type', Constants::contentTypeNormal)
            ->where('visibility', '!=', Constants::postVisibilityOnlyMe)
            ->where('post_status', Constants::postStatusPublished)
            ->limit($request->limit)
            ->get();

            $postList = GlobalFunction::processPostsListData($posts, $user);
            $adPositions = GlobalFunction::computeAdPositions(count($postList));

        return GlobalFunction::sendFeedResponse(true,'favorites posts fetched successfully', $postList, $adPositions);

    }

    public function unSavePost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
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
        $item = PostSaves::where([
            'user_id'=> $user->id,
            'post_id'=> $post->id
        ])->first();
        if($item == null){
            return GlobalFunction::sendSimpleResponse(false, 'post is not saved yet!');
        }
        $item->delete();

        GlobalFunction::settlePostSaveCount($post->id);

        AnalyticsHelper::publishEvent('unsave', $user->id, ['postId' => $post->id, 'postType' => $post->post_type]);

        return GlobalFunction::sendSimpleResponse(true, 'post unsaved successfully');

    }
    public function disLikePost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
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
        $like = PostLikes::where([
            'user_id'=> $user->id,
            'post_id'=> $post->id
        ])->first();
        if($like == null){
            return GlobalFunction::sendSimpleResponse(false, 'post is not liked yet!');
        }
        $like->delete();

        GlobalFunction::settlePostLikesCount($post->id);
        GlobalFunction::deleteNotifications(Constants::notify_like_post,$post->id,$user->id);

        AnalyticsHelper::publishEvent('unlike', $user->id, ['postId' => $post->id, 'postType' => $post->post_type]);

        return GlobalFunction::sendSimpleResponse(true, 'post disLiked successfully');

    }
    public function savePost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
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
        $item = PostSaves::where([
            'user_id'=> $user->id,
            'post_id'=> $post->id
        ])->first();
        if($item != null){
            return GlobalFunction::sendSimpleResponse(false, 'post is saved already!');
        }
        $item = new PostSaves();
        $item->post_id = $post->id;
        $item->user_id = $user->id;
        if ($request->collection_id) {
            $collection = Collection::where('id', $request->collection_id)
                ->where('user_id', $user->id)->first();
            if ($collection) {
                $item->collection_id = $collection->id;
            }
        }
        $item->save();

        // Update collection post count
        if ($item->collection_id) {
            Collection::where('id', $item->collection_id)
                ->update(['post_count' => PostSaves::where('collection_id', $item->collection_id)->count()]);
        }

        GlobalFunction::settlePostSaveCount($post->id);

        AnalyticsHelper::publishEvent('save', $user->id, ['postId' => $post->id, 'postType' => $post->post_type]);

        return GlobalFunction::sendSimpleResponse(true, 'post saved successfully');

    }
    public function likePost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
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
        $like = PostLikes::where([
            'user_id'=> $user->id,
            'post_id'=> $post->id
        ])->first();
        if($like != null){
            return GlobalFunction::sendSimpleResponse(false, 'post is liked already!');
        }
        $like = new PostLikes();
        $like->post_id = $post->id;
        $like->user_id = $user->id;
        $like->save();

        GlobalFunction::settlePostLikesCount($post->id);
        GlobalFunction::settleUserTotalPostLikesCount($post->user_id);

        // Insert Notification Data : Like Post (async via queue)
        ProcessUserNotificationJob::dispatch(Constants::notify_like_post, $user->id, $post->user_id, $post->id);

        AnalyticsHelper::publishEvent('like', $user->id, ['postId' => $post->id, 'postType' => $post->post_type, 'targetUserId' => $post->user_id]);

        return GlobalFunction::sendSimpleResponse(true, 'post liked successfully');

    }
    public function unpinPost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required|exists:tbl_post,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }
        $post = Posts::find($request->post_id);
        if($post->user_id != $user->id){
            return GlobalFunction::sendSimpleResponse(false, 'this post is not owned by you!');
        }
        if($post->is_pinned == 0){
            return GlobalFunction::sendSimpleResponse(false, 'this post is un-pinned already!');
        }
        $post->is_pinned = 0;
        $post->save();

        return GlobalFunction::sendSimpleResponse(true, 'post un-pinned successfully');

    }
    public function pinPost(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required|exists:tbl_post,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if($post->user_id != $user->id){
            return GlobalFunction::sendSimpleResponse(false, 'this post is not owned by you!');
        }

        $settings = GlobalSettings::getCached();
        $pinnedPostsCounts = Posts::where([
            'user_id'=> $user->id,
            'is_pinned'=> 1,
        ])->count();
        if($pinnedPostsCounts >= $settings->max_post_pins){
            return GlobalFunction::sendSimpleResponse(false, 'you can pin '.$settings->max_post_pins.' posts only!');
        }

        if($post->is_pinned == 1){
            return GlobalFunction::sendSimpleResponse(false, 'this post is pinned already!');
        }
        $post->is_pinned = 1;
        $post->save();

        return GlobalFunction::sendSimpleResponse(true, 'post pinned successfully');

    }
    public function updatePostCaptions(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'post_id' => 'required|exists:tbl_post,id',
            'captions' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if ($post->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'This post is not owned by you.');
        }

        $captionsData = json_decode($request->captions, true);
        if (!is_array($captionsData)) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid captions format.');
        }

        $post->captions = json_encode($captionsData);
        $post->has_captions = !empty($captionsData);
        $post->save();

        return GlobalFunction::sendSimpleResponse(true, 'Captions updated successfully.');
    }

    public function increaseShareCount(Request $request){
        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $post =  Posts::find($request->post_id);

        if($post == null){
            return GlobalFunction::sendSimpleResponse(false, 'post does not exists!');
        }

        $post->shares += 1;
        $post->save();

        $token = $request->header('authtoken');
        $shareUser = GlobalFunction::getUserFromAuthToken($token);
        if ($shareUser) {
            AnalyticsHelper::publishEvent('share', $shareUser->id, ['postId' => $post->id, 'postType' => $post->post_type]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'post share count increased successfully');

    }

    public function generateEmbedCode(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:tbl_post,id',
        ]);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $baseUrl = url('/');
        $embedUrl = "{$baseUrl}/embed/{$request->post_id}";
        $embedCode = '<iframe src="' . $embedUrl . '" width="400" height="720" frameborder="0" allowfullscreen style="border-radius:12px;max-width:100%;"></iframe>';

        return GlobalFunction::sendDataResponse(true, 'Embed code generated', [
            'embed_url' => $embedUrl,
            'embed_code' => $embedCode,
        ]);
    }

    public function increaseViewsCount(Request $request){
        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $post =  Posts::find($request->post_id);

        if($post == null){
            return GlobalFunction::sendSimpleResponse(false, 'post does not exists!');
        }

        $post->views += 1;
        $post->save();

        $token = $request->header('authtoken');
        $viewUser = GlobalFunction::getUserFromAuthToken($token);
        if ($viewUser) {
            AnalyticsHelper::publishEvent('view', $viewUser->id, ['postId' => $post->id, 'postType' => $post->post_type]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'post view increased successfully');

    }

    public function addPost_Feed_Text(Request $request){
        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'can_comment' => 'required',
            'visibility' => 'nullable|integer|in:0,1,2,3',
            'description' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $post = GlobalFunction::generatePost($request, Constants::postTypeText, $user, null);

        return GlobalFunction::sendDataResponse(true, 'feed text : post uploaded successfully', $post);

    }
    public function fetchPostById(Request $request){

        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required',
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
        $post->is_liked = PostLikes::where('post_id', $post->id)->where('user_id', $user->id)->exists();
        $post->is_saved = PostSaves::where('post_id', $post->id)->where('user_id', $user->id)->exists();
        $post->user->is_following = Followers::where('from_user_id', $user->id)->where('to_user_id', $post->user_id)->exists();
        $post->mentioned_users = Users::whereIn('id', explode(',', $post->mentioned_user_ids))->select(explode(',',Constants::userPublicFields))->get();

        $data['post'] = $post;

        if($request->has('comment_id')){
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
            $data['comment'] = $comment;
        }
        // Try to fetch comment
        if($request->has('comment_id')){
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
            $data['comment'] = $comment;
        }
        if($request->has('reply_id')){
            $reply = CommentReplies::where('id',$request->reply_id)
                    ->with(['user:'.Constants::userPublicFields])
                    ->first();

            if($reply == null){
                return GlobalFunction::sendSimpleResponse(false, 'reply does not exists!');
            }
            $reply->mentionedUsers = Users::whereIn('id', explode(',', $reply->mentioned_user_ids))
                                        ->select(explode(',',Constants::userPublicFields))
                                        ->get();

            $data['reply'] = $reply;
        }

        return GlobalFunction::sendDataResponse(true, 'post fetched successfuly', $data);

    }
    public function addPost_Feed_Image(Request $request){
        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'can_comment' => 'required',
            'visibility' => 'nullable|integer|in:0,1,2,3',
            'post_images' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $post = GlobalFunction::generatePost($request, Constants::postTypeImage, $user, null);

        return GlobalFunction::sendDataResponse(true, 'feed images : post uploaded successfully', $post);

    }
    public function addPost_Feed_Video(Request $request){
        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'can_comment' => 'required',
            'visibility' => 'nullable|integer|in:0,1,2,3',
            'video' => 'required',
            'thumbnail' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $post = GlobalFunction::generatePost($request, Constants::postTypeVideo, $user, null);

         return GlobalFunction::sendDataResponse(true, 'feed video : post uploaded successfully', $post);

    }
    public function addPost_Reel(Request $request){
        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'can_comment' => 'required',
            'visibility' => 'nullable|integer|in:0,1,2,3',
            'video' => 'required',
            'thumbnail' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }


        $sound = null;
        if($request->has('sound_id')){
            $sound = Musics::find($request->sound_id);
            if ($sound == null) {
                return response()->json(['status' => false, 'message' => "Sound doesn't exists !"]);
            }
        }
        $post = GlobalFunction::generatePost($request, Constants::postTypeReel, $user, $sound);

        return GlobalFunction::sendDataResponse(true, 'reel : post uploaded successfully', $post);

    }

    public function addUserMusic(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'sound' => 'required',
            'duration' => 'required',
            'artist' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => "this user is freezed!"]);
        }

        $music = new Musics();
        $music->title = $request->title;
        $music->sound = GlobalFunction::saveFileAndGivePath($request->sound);
        if($request->has('image')){
            $music->image = GlobalFunction::saveFileAndGivePath($request->image);
        }
        $music->duration = $request->duration;
        $music->artist = $request->artist;
        $music->added_by = Constants::userTypeUser;
        $music->user_id = $user->id;
        $music->save();

        $music = Musics::where('id', $music->id)->with(['user:'.Constants::userPublicFields])->first();
        return GlobalFunction::sendDataResponse(true, 'music added successfully', $music);

    }

    // ================================================================
    // DUET ENDPOINTS
    // ================================================================

    public function fetchDuetsOfPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $limit = $request->has('limit') ? $request->limit : 20;
        $lastItemId = $request->has('last_item_id') ? $request->last_item_id : null;

        $query = Posts::where('duet_source_post_id', $request->post_id)
            ->where('content_type', Constants::contentTypeNormal)
            ->with(Constants::postsWithArray)
            ->orderBy('id', 'desc');

        if ($lastItemId) {
            $query->where('id', '<', $lastItemId);
        }

        $duets = $query->limit($limit)->get();

        // Add is_liked and is_saved for current user
        foreach ($duets as $post) {
            $post->is_liked = PostLikes::where('post_id', $post->id)->where('user_id', $user->id)->exists();
            $post->is_saved = PostSaves::where('post_id', $post->id)->where('user_id', $user->id)->exists();
        }

        return GlobalFunction::sendDataResponse(true, 'Duets fetched successfully', $duets);
    }

    public function fetchDuetCount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $count = Posts::where('duet_source_post_id', $request->post_id)->count();
        return GlobalFunction::sendDataResponse(true, 'Duet count', ['count' => $count]);
    }

    // ================================================================
    // STITCH ENDPOINTS
    // ================================================================

    public function fetchStitchesOfPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $limit = $request->has('limit') ? $request->limit : 20;
        $lastItemId = $request->has('last_item_id') ? $request->last_item_id : null;

        $query = Posts::where('stitch_source_post_id', $request->post_id)
            ->where('content_type', Constants::contentTypeNormal)
            ->with(Constants::postsWithArray)
            ->orderBy('id', 'desc');

        if ($lastItemId) {
            $query->where('id', '<', $lastItemId);
        }

        $stitches = $query->limit($limit)->get();

        foreach ($stitches as $post) {
            $post->is_liked = PostLikes::where('post_id', $post->id)->where('user_id', $user->id)->exists();
            $post->is_saved = PostSaves::where('post_id', $post->id)->where('user_id', $user->id)->exists();
        }

        return GlobalFunction::sendDataResponse(true, 'Stitches fetched successfully', $stitches);
    }

    // ================================================================
    // CONTENT TYPE ENDPOINTS (Music Videos, Trailers, News)
    // ================================================================

    public function addPost_MusicVideo(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validation = GlobalFunction::validateContentTypeUpload($user, Constants::contentTypeMusicVideo);
        if (!$validation['status']) {
            return response()->json($validation);
        }

        $rules = [
            'can_comment' => 'required',
            'video' => 'required',
            'thumbnail' => 'required',
            'content_metadata' => 'required|json',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $metadata = json_decode($request->content_metadata, true);
        if (!isset($metadata['genre']) || !isset($metadata['language'])) {
            return response()->json(['status' => false, 'message' => 'genre and language are required in content_metadata']);
        }

        $post = GlobalFunction::generatePost($request, Constants::postTypeVideo, $user, null);
        $post->content_type = Constants::contentTypeMusicVideo;
        $post->content_metadata = $request->content_metadata;
        if ($request->has('linked_previous_post_id')) {
            $post->linked_previous_post_id = $request->linked_previous_post_id;
        }
        $post->save();

        $post = GlobalFunction::preparePostFullData($post->id);

        return GlobalFunction::sendDataResponse(true, 'Music video uploaded successfully', $post);
    }

    public function addPost_Trailer(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validation = GlobalFunction::validateContentTypeUpload($user, Constants::contentTypeTrailer);
        if (!$validation['status']) {
            return response()->json($validation);
        }

        $rules = [
            'can_comment' => 'required',
            'video' => 'required',
            'thumbnail' => 'required',
            'content_metadata' => 'required|json',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $metadata = json_decode($request->content_metadata, true);
        if (!isset($metadata['genre']) || !isset($metadata['language'])) {
            return response()->json(['status' => false, 'message' => 'genre and language are required in content_metadata']);
        }

        $post = GlobalFunction::generatePost($request, Constants::postTypeVideo, $user, null);
        $post->content_type = Constants::contentTypeTrailer;
        $post->content_metadata = $request->content_metadata;
        if ($request->has('linked_previous_post_id')) {
            $post->linked_previous_post_id = $request->linked_previous_post_id;
        }
        $post->save();

        $post = GlobalFunction::preparePostFullData($post->id);

        return GlobalFunction::sendDataResponse(true, 'Trailer uploaded successfully', $post);
    }

    public function addPost_News(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validation = GlobalFunction::validateContentTypeUpload($user, Constants::contentTypeNews);
        if (!$validation['status']) {
            return response()->json($validation);
        }

        $rules = [
            'can_comment' => 'required',
            'video' => 'required',
            'thumbnail' => 'required',
            'content_metadata' => 'required|json',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $metadata = json_decode($request->content_metadata, true);
        if (!isset($metadata['category'])) {
            return response()->json(['status' => false, 'message' => 'category is required in content_metadata']);
        }

        $post = GlobalFunction::generatePost($request, Constants::postTypeVideo, $user, null);
        $post->content_type = Constants::contentTypeNews;
        $post->content_metadata = $request->content_metadata;
        if ($request->has('linked_previous_post_id')) {
            $post->linked_previous_post_id = $request->linked_previous_post_id;
        }
        $post->save();

        $post = GlobalFunction::preparePostFullData($post->id);

        return GlobalFunction::sendDataResponse(true, 'News post uploaded successfully', $post);
    }

    public function fetchContentByType(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'content_type' => 'required|integer|in:1,2,3,4',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $contentType = (int) $request->content_type;
        $subTab = $request->get('sub_tab', 'for_you');
        $limit = (int) $request->get('limit', 20);
        $genre = $request->get('genre');
        $language = $request->get('language');

        $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

        $query = Posts::with(Constants::postsWithArray)
            ->whereHas('user', function ($q) {
                $q->where('is_freez', 0);
            })
            ->whereNotIn('user_id', $blockedUserIds)
            ->where('content_type', $contentType)
            ->where('visibility', Constants::postVisibilityPublic);

        // Genre filter via JSONB
        if ($genre) {
            $query->whereRaw("content_metadata->>'genre' = ?", [$genre]);
        }
        // Language filter via JSONB
        if ($language) {
            $query->whereRaw("content_metadata->>'language' = ?", [$language]);
        }

        // Sub-tab logic
        switch ($subTab) {
            case 'following':
                $followingUserIds = Followers::where('from_user_id', $user->id)->pluck('to_user_id');
                $query->whereIn('user_id', $followingUserIds);
                // Allow followers-only posts for followed users
                $query->where('visibility', '!=', Constants::postVisibilityOnlyMe);
                $query->inRandomOrder();
                break;
            case 'trending':
                $query->orderByRaw('(views * 1 + likes * 3 + shares * 5) DESC');
                break;
            case 'for_you':
            default:
                // Featured posts first, then random
                $query->orderByRaw('is_featured DESC, RANDOM()');
                break;
        }

        // Cursor pagination
        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $query->limit($limit);
        $posts = $query->get();

        $postList = GlobalFunction::processPostsListData($posts, $user);
        $adPositions = GlobalFunction::computeAdPositions(count($postList));

        return GlobalFunction::sendFeedResponse(true, 'Content fetched successfully', $postList, $adPositions);
    }

    public function fetchContentGenres(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'content_type' => 'required|integer|in:1,2,3,4',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $ct = $request->content_type;
        $genres = Cache::remember("content_genres:{$ct}", 1800, function () use ($ct) {
            return ContentGenre::where('content_type', $ct)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return GlobalFunction::sendDataResponse(true, 'Content genres fetched', $genres);
    }

    public function fetchContentLanguages(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $languages = Cache::remember('content_languages', 1800, function () {
            return ContentLanguage::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return GlobalFunction::sendDataResponse(true, 'Content languages fetched', $languages);
    }

    // ================================================================
    // PHASE 14: Enhanced Search & Discovery
    // ================================================================

    /**
     * Enhanced search using PostgreSQL full-text search with fallback to LIKE.
     * Searches posts, optionally filtered by content_type.
     */
    public function searchPostsFTS(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['limit' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
        $search = GlobalFunction::cleanString($request->keyword ?? '');
        $limit = (int) $request->limit;

        $query = Posts::whereHas('user', function ($q) {
                $q->where('is_freez', 0);
            })
            ->with(Constants::postsWithArray)
            ->whereNotIn('user_id', $blockedUserIds);

        // Filter by content_type if provided (0=normal, 1=music, 2=trailer, 3=news, 4=story)
        if ($request->has('content_type')) {
            $query->where('content_type', (int) $request->content_type);
        } else {
            // Default: search normal posts
            $query->where('content_type', Constants::contentTypeNormal);
        }

        // Filter by post types if provided
        if ($request->has('types')) {
            $query->whereIn('post_type', explode(',', $request->types));
        }

        // Full-text search on description using PostgreSQL tsvector
        if (!empty($search)) {
            $tsQuery = implode(' & ', array_filter(explode(' ', $search)));
            $query->where(function ($q) use ($search, $tsQuery) {
                $q->whereRaw(
                    "to_tsvector('english', COALESCE(description, '')) @@ plainto_tsquery('english', ?)",
                    [$search]
                )
                // Also search in content_metadata JSONB for artist, genre, etc.
                ->orWhereRaw("content_metadata::text ILIKE ?", ["%{$search}%"])
                // Fallback to ILIKE for non-English text
                ->orWhere('description', 'ILIKE', "%{$search}%");
            });

            // Order by relevance (FTS rank) + recency
            $query->orderByRaw(
                "ts_rank(to_tsvector('english', COALESCE(description, '')), plainto_tsquery('english', ?)) DESC, id DESC",
                [$search]
            );
        } else {
            $query->orderBy('id', 'DESC');
        }

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        // Filter by genre (from content_metadata)
        if ($request->has('genre')) {
            $query->whereRaw("content_metadata->>'genre' = ?", [$request->genre]);
        }

        // Filter by language (from content_metadata)
        if ($request->has('language')) {
            $query->whereRaw("content_metadata->>'language' = ?", [$request->language]);
        }

        $posts = $query->limit($limit)->get();

        // Track search for insights (only first page, non-empty keyword)
        if (!$request->has('last_item_id') && !empty($search)) {
            try {
                \App\Models\SearchHistory::create([
                    'user_id' => $user->id,
                    'keyword' => mb_substr($search, 0, 255),
                    'search_type' => 'posts_fts',
                    'result_count' => $posts->count(),
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {}
        }

        $postList = GlobalFunction::processPostsListData($posts, $user);

        return GlobalFunction::sendDataResponse(true, 'search results', $postList);
    }

    /**
     * Enhanced explore page with featured content, trending hashtags,
     * popular creators, and category-based content sections.
     */
    public function fetchEnhancedExplore(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
        $data = [];

        // 1. Featured content (admin-curated, is_featured = true)
        $featured = Posts::where('is_featured', true)
            ->whereHas('user', fn($q) => $q->where('is_freez', 0))
            ->with(Constants::postsWithArray)
            ->whereNotIn('user_id', $blockedUserIds)
            ->orderBy('updated_at', 'DESC')
            ->limit(10)
            ->get();
        $data['featured'] = GlobalFunction::processPostsListData($featured, $user);

        // 2. Trending hashtags (top 20 by post_count)
        $data['trending_hashtags'] = Hashtags::where('post_count', '>=', 2)
            ->orderBy('post_count', 'DESC')
            ->limit(20)
            ->get();

        // 3. Popular creators (users with most followers, active recently)
        $data['popular_creators'] = Users::where('is_freez', 0)
            ->whereNotIn('id', $blockedUserIds)
            ->where('id', '!=', $user->id)
            ->orderBy('follower_count', 'DESC')
            ->select(explode(',', Constants::userPublicFields))
            ->limit(15)
            ->get();

        // 4. Content sections — sample from each content type
        $contentTypes = [
            ['type' => Constants::contentTypeMusicVideo, 'label' => 'Music Videos'],
            ['type' => Constants::contentTypeTrailer, 'label' => 'Trailers'],
            ['type' => Constants::contentTypeNews, 'label' => 'News'],
        ];
        $contentSections = [];
        foreach ($contentTypes as $ct) {
            $posts = Posts::where('content_type', $ct['type'])
                ->whereHas('user', fn($q) => $q->where('is_freez', 0))
                ->with(Constants::postsWithArray)
                ->whereNotIn('user_id', $blockedUserIds)
                ->orderByRaw('(views * 1 + likes * 3 + shares * 5) DESC')
                ->limit(6)
                ->get();
            if ($posts->count() > 0) {
                $contentSections[] = [
                    'content_type' => $ct['type'],
                    'label' => $ct['label'],
                    'posts' => GlobalFunction::processPostsListData($posts, $user),
                ];
            }
        }
        $data['content_sections'] = $contentSections;

        // 5. Trending searches (placeholder — can be populated from search logs later)
        $data['trending_searches'] = [];

        return GlobalFunction::sendDataResponse(true, 'enhanced explore data', $data);
    }

    public function fetchLinkedPost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'post_id' => 'required|exists:tbl_post,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        $data = [];

        // Previous part
        if ($post->linked_previous_post_id) {
            $prevPost = GlobalFunction::preparePostFullData($post->linked_previous_post_id);
            if ($prevPost) {
                $prevPost->is_liked = PostLikes::where('post_id', $prevPost->id)->where('user_id', $user->id)->exists();
                $prevPost->is_saved = PostSaves::where('post_id', $prevPost->id)->where('user_id', $user->id)->exists();
                $data['previous_post'] = $prevPost;
            }
        }

        // Next part (reverse lookup)
        $nextPost = Posts::where('linked_previous_post_id', $post->id)
            ->with(Constants::postsWithArray)
            ->first();
        if ($nextPost) {
            $nextPost->is_liked = PostLikes::where('post_id', $nextPost->id)->where('user_id', $user->id)->exists();
            $nextPost->is_saved = PostSaves::where('post_id', $nextPost->id)->where('user_id', $user->id)->exists();
            $nextPost->mentioned_users = Users::whereIn('id', explode(',', $nextPost->mentioned_user_ids))
                ->select(explode(',', Constants::userPublicFields))
                ->get();
            $data['next_post'] = $nextPost;
        }

        return GlobalFunction::sendDataResponse(true, 'Linked posts fetched', $data);
    }

    // ================================================================
    // ADMIN: Content type listing methods
    // ================================================================

    public function contentMusic()
    {
        return view('content_music');
    }

    public function contentTrailers()
    {
        return view('content_trailers');
    }

    public function contentNews()
    {
        return view('content_news');
    }

    public function listContentPosts(Request $request, int $contentType)
    {
        $query = Posts::query()->where('content_type', $contentType);
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('description', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($post) {
            $view = GlobalFunction::createPostDetailsButton($post->id);
            $delete = GlobalFunction::createPostDeleteButton($post->id);
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$delete}</span>";
            $postUser = GlobalFunction::createUserDetailsColumn($post->user_id);
            $states = GlobalFunction::createPostStatesView($post->id);
            $postType = GlobalFunction::createPostTypeBadge($post->id);

            $metadata = $post->content_metadata ? json_decode($post->content_metadata, true) : [];
            $genre = $metadata['genre'] ?? $metadata['category'] ?? '-';
            $language = $metadata['language'] ?? '-';
            $metaInfo = "<span class='badge bg-info-subtle text-info'>{$genre}</span> <span class='badge bg-secondary-subtle text-secondary'>{$language}</span>";

            $featured = ($post->is_featured ?? false) ? "<span class='badge bg-warning'>Featured</span>" : '';

            $formattedDesc = GlobalFunction::formatDescription($post->description);
            $descAndStates = '<div class="itemDescription">'.$states.$formattedDesc.'</div>';

            $viewContent = GlobalFunction::createViewContentButton($post);

            return [
                $viewContent,
                $postType,
                $postUser,
                $metaInfo,
                $featured,
                $descAndStates,
                GlobalFunction::formateDatabaseTime($post->created_at),
                $action
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function listMusicVideoPosts(Request $request)
    {
        return $this->listContentPosts($request, Constants::contentTypeMusicVideo);
    }

    public function listTrailerPosts_Content(Request $request)
    {
        return $this->listContentPosts($request, Constants::contentTypeTrailer);
    }

    public function listNewsPosts_Content(Request $request)
    {
        return $this->listContentPosts($request, Constants::contentTypeNews);
    }

    public function toggleFeaturedPost(Request $request)
    {
        $post = Posts::find($request->id);
        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'Post not found');
        }
        $post->is_featured = !$post->is_featured;
        $post->save();

        return GlobalFunction::sendSimpleResponse(true, $post->is_featured ? 'Post marked as featured' : 'Post unfeatured');
    }

    // ─── Save Collections ────────────────────────────────────────

    public function fetchCollections(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $collections = Collection::where('user_id', $user->id)
            ->with(['coverPost:id,thumbnail,post_type'])
            ->withCount('acceptedMembers')
            ->orderBy('is_default', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get();

        // Also count "All Saved" (posts with no collection)
        $allSavedCount = PostSaves::where('user_id', $user->id)->count();

        $data = [
            'all_saved_count' => $allSavedCount,
            'collections' => $collections,
        ];

        return GlobalFunction::sendDataResponse(true, 'collections fetched', $data);
    }

    public function createCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['name' => 'required|string|max:100'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collection = new Collection();
        $collection->user_id = $user->id;
        $collection->name = $request->name;
        $collection->save();

        return GlobalFunction::sendDataResponse(true, 'collection created', $collection);
    }

    public function editCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['collection_id' => 'required', 'name' => 'required|string|max:100'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collection = Collection::where('id', $request->collection_id)
            ->where('user_id', $user->id)->first();
        if (!$collection) {
            return GlobalFunction::sendSimpleResponse(false, 'Collection not found');
        }
        if ($collection->is_default) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot rename default collection');
        }

        $collection->name = $request->name;
        $collection->save();

        return GlobalFunction::sendSimpleResponse(true, 'collection updated');
    }

    public function deleteCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $collection = Collection::where('id', $request->collection_id)
            ->where('user_id', $user->id)->first();
        if (!$collection) {
            return GlobalFunction::sendSimpleResponse(false, 'Collection not found');
        }
        if ($collection->is_default) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot delete default collection');
        }

        // Move saved posts back to no collection
        PostSaves::where('collection_id', $collection->id)->update(['collection_id' => null]);
        $collection->delete();

        return GlobalFunction::sendSimpleResponse(true, 'collection deleted');
    }

    public function movePostToCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['save_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $save = PostSaves::where('id', $request->save_id)
            ->where('user_id', $user->id)->first();
        if (!$save) {
            return GlobalFunction::sendSimpleResponse(false, 'Saved post not found');
        }

        $oldCollectionId = $save->collection_id;
        $newCollectionId = $request->collection_id ?: null;

        if ($newCollectionId) {
            $collection = Collection::where('id', $newCollectionId)
                ->where('user_id', $user->id)->first();
            if (!$collection) {
                return GlobalFunction::sendSimpleResponse(false, 'Collection not found');
            }
        }

        $save->collection_id = $newCollectionId;
        $save->save();

        // Update counts
        if ($oldCollectionId) {
            Collection::where('id', $oldCollectionId)
                ->update(['post_count' => PostSaves::where('collection_id', $oldCollectionId)->count()]);
        }
        if ($newCollectionId) {
            Collection::where('id', $newCollectionId)
                ->update([
                    'post_count' => PostSaves::where('collection_id', $newCollectionId)->count(),
                    'cover_post_id' => $save->post_id,
                ]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'post moved to collection');
    }

    public function fetchCollectionPosts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['collection_id' => 'required', 'limit' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collection = Collection::find($request->collection_id);
        if (!$collection) {
            return GlobalFunction::sendSimpleResponse(false, 'Collection not found');
        }

        // Check access: owner OR accepted member of shared collection
        $isOwner = $collection->user_id == $user->id;
        $isMember = $collection->is_shared && CollectionMember::where('collection_id', $collection->id)
            ->where('user_id', $user->id)
            ->where('status', CollectionMember::STATUS_ACCEPTED)
            ->exists();
        if (!$isOwner && !$isMember) {
            return GlobalFunction::sendSimpleResponse(false, 'Access denied');
        }

        // For shared collections, show all posts regardless of who saved them
        $query = PostSaves::where('collection_id', $request->collection_id)
            ->whereHas('post')
            ->with(['post.images', 'post.music', 'post.user:' . Constants::userPublicFields])
            ->orderBy('id', 'DESC')
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $postSaves = $query->get();

        $postList = [];
        foreach ($postSaves as $save) {
            $post = $save->post;
            if ($post) {
                $post->post_save_id = $save->id;
                $postList[] = $post;
            }
        }

        $processed = GlobalFunction::processPostsListData(collect($postList), $user);
        return GlobalFunction::sendDataResponse(true, 'collection posts fetched', $processed);
    }

    // ─── Shared Collections ────────────────────────────────────────

    public function shareCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['collection_id' => 'required', 'user_ids' => 'required|array'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collection = Collection::where('id', $request->collection_id)
            ->where('user_id', $user->id)->first();
        if (!$collection) {
            return GlobalFunction::sendSimpleResponse(false, 'Collection not found');
        }
        if ($collection->is_default) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot share default collection');
        }

        // Mark as shared
        $collection->is_shared = true;
        $collection->save();

        $invited = 0;
        foreach ($request->user_ids as $userId) {
            $exists = CollectionMember::where('collection_id', $collection->id)
                ->where('user_id', $userId)->exists();
            if (!$exists && $userId != $user->id) {
                CollectionMember::create([
                    'collection_id' => $collection->id,
                    'user_id' => $userId,
                    'role' => CollectionMember::ROLE_MEMBER,
                    'status' => CollectionMember::STATUS_PENDING,
                    'invited_by' => $user->id,
                ]);
                $invited++;
            }
        }

        return GlobalFunction::sendDataResponse(true, "$invited invite(s) sent", [
            'collection' => $collection,
            'invited_count' => $invited,
        ]);
    }

    public function respondCollectionInvite(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['member_id' => 'required', 'accept' => 'required|boolean'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $member = CollectionMember::where('id', $request->member_id)
            ->where('user_id', $user->id)
            ->where('status', CollectionMember::STATUS_PENDING)
            ->first();
        if (!$member) {
            return GlobalFunction::sendSimpleResponse(false, 'Invite not found');
        }

        $member->status = $request->accept
            ? CollectionMember::STATUS_ACCEPTED
            : CollectionMember::STATUS_DECLINED;
        $member->save();

        return GlobalFunction::sendSimpleResponse(true, $request->accept ? 'Invite accepted' : 'Invite declined');
    }

    public function fetchCollectionInvites(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $invites = CollectionMember::where('user_id', $user->id)
            ->where('status', CollectionMember::STATUS_PENDING)
            ->with(['collection:id,name,post_count,cover_post_id', 'collection.coverPost:id,thumbnail,post_type', 'inviter:' . Constants::userPublicFields])
            ->orderBy('created_at', 'DESC')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'invites fetched', $invites);
    }

    public function fetchCollectionMembers(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $collection = Collection::where('id', $request->collection_id)->first();
        if (!$collection) {
            return GlobalFunction::sendSimpleResponse(false, 'Collection not found');
        }

        // Must be owner or accepted member
        $isOwner = $collection->user_id == $user->id;
        $isMember = CollectionMember::where('collection_id', $collection->id)
            ->where('user_id', $user->id)
            ->where('status', CollectionMember::STATUS_ACCEPTED)
            ->exists();
        if (!$isOwner && !$isMember) {
            return GlobalFunction::sendSimpleResponse(false, 'Access denied');
        }

        $members = CollectionMember::where('collection_id', $collection->id)
            ->with(['user:' . Constants::userPublicFields])
            ->orderBy('role', 'DESC')
            ->orderBy('created_at', 'ASC')
            ->get();

        $data = [
            'owner' => Users::select(explode(',', Constants::userPublicFields))->find($collection->user_id),
            'members' => $members,
        ];

        return GlobalFunction::sendDataResponse(true, 'members fetched', $data);
    }

    public function removeCollectionMember(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['collection_id' => 'required', 'user_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collection = Collection::where('id', $request->collection_id)
            ->where('user_id', $user->id)->first();
        if (!$collection) {
            return GlobalFunction::sendSimpleResponse(false, 'Only owner can remove members');
        }

        CollectionMember::where('collection_id', $collection->id)
            ->where('user_id', $request->user_id)
            ->delete();

        // If no members left, mark as not shared
        $remainingMembers = CollectionMember::where('collection_id', $collection->id)
            ->where('status', CollectionMember::STATUS_ACCEPTED)->count();
        if ($remainingMembers == 0) {
            $collection->is_shared = false;
            $collection->save();
        }

        return GlobalFunction::sendSimpleResponse(true, 'Member removed');
    }

    public function leaveCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        CollectionMember::where('collection_id', $request->collection_id)
            ->where('user_id', $user->id)
            ->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Left collection');
    }

    public function savePostToSharedCollection(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['post_id' => 'required', 'collection_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collection = Collection::find($request->collection_id);
        if (!$collection || !$collection->is_shared) {
            return GlobalFunction::sendSimpleResponse(false, 'Shared collection not found');
        }

        // Must be owner or accepted member
        $isOwner = $collection->user_id == $user->id;
        $isMember = CollectionMember::where('collection_id', $collection->id)
            ->where('user_id', $user->id)
            ->where('status', CollectionMember::STATUS_ACCEPTED)
            ->exists();
        if (!$isOwner && !$isMember) {
            return GlobalFunction::sendSimpleResponse(false, 'Access denied');
        }

        // Check if post already in this collection by any user
        $exists = PostSaves::where('post_id', $request->post_id)
            ->where('collection_id', $collection->id)->exists();
        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'Post already in this collection');
        }

        $save = new PostSaves();
        $save->post_id = $request->post_id;
        $save->user_id = $user->id;
        $save->collection_id = $collection->id;
        $save->save();

        $collection->post_count = PostSaves::where('collection_id', $collection->id)->count();
        $collection->cover_post_id = $save->post_id;
        $collection->save();

        return GlobalFunction::sendSimpleResponse(true, 'Post saved to shared collection');
    }

    public function fetchSharedCollections(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        // Collections shared with the user (accepted invites)
        $memberCollectionIds = CollectionMember::where('user_id', $user->id)
            ->where('status', CollectionMember::STATUS_ACCEPTED)
            ->pluck('collection_id');

        $collections = Collection::whereIn('id', $memberCollectionIds)
            ->with(['coverPost:id,thumbnail,post_type', 'user:' . Constants::userPublicFields])
            ->withCount('acceptedMembers')
            ->orderBy('updated_at', 'DESC')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'shared collections fetched', $collections);
    }

    // ─── Scheduled Posts ────────────────────────────────────────────

    public function fetchScheduledPosts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $posts = Posts::where('user_id', $user->id)
            ->where('post_status', Constants::postStatusScheduled)
            ->with(Constants::postsWithArray)
            ->orderBy('scheduled_at', 'ASC')
            ->get();

        $postList = GlobalFunction::processPostsListData($posts, $user);
        return GlobalFunction::sendDataResponse(true, 'scheduled posts fetched', $postList);
    }

    public function cancelScheduledPost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['post_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::where('id', $request->post_id)
            ->where('user_id', $user->id)
            ->where('post_status', Constants::postStatusScheduled)
            ->first();

        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'scheduled post not found');
        }

        $post->delete();
        return GlobalFunction::sendSimpleResponse(true, 'scheduled post cancelled');
    }

    // ─── Not Interested ─────────────────────────────────────────────

    public function markNotInterested(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['post_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        NotInterested::firstOrCreate([
            'user_id' => $user->id,
            'post_id' => $request->post_id,
        ]);

        GlobalFunction::clearNotInterestedCache($user->id);

        return GlobalFunction::sendSimpleResponse(true, 'marked as not interested');
    }

    public function undoNotInterested(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['post_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        NotInterested::where('user_id', $user->id)
            ->where('post_id', $request->post_id)
            ->delete();

        GlobalFunction::clearNotInterestedCache($user->id);

        return GlobalFunction::sendSimpleResponse(true, 'not interested removed');
    }

    public function fetchSubscriberOnlyPosts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'creator_id' => 'required|exists:tbl_users,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $creatorId = $request->creator_id;
        $limit = (int) $request->get('limit', 20);
        $offset = (int) $request->get('offset', 0);

        // Only creator themselves or active subscribers can view exclusive content
        $isCreator = $user->id == $creatorId;
        if (!$isCreator) {
            $isSubscribed = CreatorSubscription::where('subscriber_id', $user->id)
                ->where('creator_id', $creatorId)
                ->active()
                ->exists();
            if (!$isSubscribed) {
                return GlobalFunction::sendDataResponse(true, 'subscriber only posts', []);
            }
        }

        $posts = Posts::where('user_id', $creatorId)
            ->where('visibility', Constants::postVisibilitySubscribers)
            ->where('post_status', Constants::postStatusPublished)
            ->with(Constants::postsWithArray)
            ->orderBy('id', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $postList = GlobalFunction::processPostsListData($posts, $user);

        return GlobalFunction::sendDataResponse(true, 'subscriber only posts fetched successfully', $postList);
    }

    private function getRecommendedPostIds(int $userId, int $limit, int $offset): array
    {
        $settings = GlobalSettings::getCached();
        $baseUrl = $settings->analytics_base_url ?? 'http://127.0.0.1:3001';
        $apiKey = $settings->analytics_api_key ?? null;

        if (empty($apiKey)) {
            return [];
        }

        try {
            $response = Http::timeout(3)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->get("{$baseUrl}/api/recommendations/feed", [
                    'userId' => $userId,
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['postIds'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::debug('Recommendation service unavailable', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
