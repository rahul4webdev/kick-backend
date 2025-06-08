<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use File;
use Session;
use DB;
use Log;
use Storage;
use App\User;
use App\Common;
use App\Admin;
use App\ProfileCategory;
use App\Post;
use App\HashTags;
use App\Sound;
use App\SoundCategory;
use App\Followers;
use App\Like;
use App\Bookmark;
use App\Comments;
use App\Notification;
use App\Report;
use App\BlockUser;
use App\GlobalFunction;
use App\GlobalSettings;
use Illuminate\Support\Carbon;

class PostController extends Controller
{

    public function getUserVideos(Request $request)
    {


        // $user_id = $request->user()->user_id;
        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
            'user_id' => 'required',
            'my_user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $user_id = $request->get('user_id');
        $my_user_id = $request->get('my_user_id');

        $user_videos = Post::where('user_id', $user_id)->orderBy('post_id', 'DESC')->offset($start)->limit($limit)->get();

        $i = 0;
        $postData = [];
        if (count($user_videos) > 0) {

            foreach ($user_videos as $post_data_value) {
                $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                $soundData  = Sound::where('sound_id', $post_data_value['sound_id'])->first();
                $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $my_user_id)->first();
                $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $my_user_id)->first();
                $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $my_user_id)->first();
                $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

                $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                $postData[$i]['full_name'] = $userData['full_name'];
                $postData[$i]['user_name'] = $userData['user_name'];
                $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                $postData[$i]['post_description'] = $post_data_value['post_description'];
                $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                $postData[$i]['post_video'] = $post_data_value['post_video'];
                $postData[$i]['post_image'] = $post_data_value['post_image'];
                $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                $postData[$i]['sound_title'] = $soundData['sound_title'];
                $postData[$i]['duration'] = $soundData['duration'];
                $postData[$i]['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
                $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                $postData[$i]['post_likes_count'] = (int)$post_likes_count;
                $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;

                $i++;
            }

            return response()->json(['status' => 200, 'message' => "User Videos Data Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $postData]);
        }
    }

    public function uploadFileGivePath(Request $request)
    {
        if ($request->hasfile('file')) {
            $file = $request->file('file');
            $filePath = GlobalFunction::uploadFilToS3($file);

            return response()->json(['status' => 200, 'message' => "File Added Successfully.", 'path' => $filePath]);
        }
    }

    public function getUserLikesVideos(Request $request)
    {




        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
            'user_id' => 'required',
            'my_user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $user_id = $request->get('user_id');
        $my_user_id = $request->get('my_user_id');

        $user_videos  = Like::select('p.*')->join('tbl_post as p', 'p.post_id', 'tbl_likes.post_id')->where('tbl_likes.user_id', $user_id)->orderBy('tbl_likes.like_id', 'DESC')->offset($start)->limit($limit)->get();

        $i = 0;
        $postData = [];
        if (count($user_videos) > 0) {

            foreach ($user_videos as $post_data_value) {
                $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                $soundData  = Sound::where('sound_id', $post_data_value['sound_id'])->first();
                $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $my_user_id)->first();
                $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

                $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                $postData[$i]['full_name'] = $userData['full_name'];
                $postData[$i]['user_name'] = $userData['user_name'];
                $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";;
                $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                $postData[$i]['post_description'] = $post_data_value['post_description'];
                $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                $postData[$i]['post_video'] = $post_data_value['post_video'];
                $postData[$i]['post_image'] = $post_data_value['post_image'];
                $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                $postData[$i]['sound_title'] = $soundData['sound_title'];
                $postData[$i]['duration'] = $soundData['duration'];
                $postData[$i]['singer'] = $soundData['singer'];
                $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                $postData[$i]['post_likes_count'] = (int)$post_likes_count;
                $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;

                $i++;
            }

            return response()->json(['status' => 200, 'message' => "User likes Video Data Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $postData]);
        }
    }

    public function addPost(Request $request)
    {

        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'is_orignal_sound' => 'required',
            // 'post_description' => 'required',
            // 'post_hash_tag' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        // Max upload daily filter
        $settings = GlobalSettings::first();
        $postsCount = Post::where('user_id', $user_id)->whereDate('created_at', Carbon::today())->count();
        if ($postsCount >= $settings->max_upload_daily) {
            return response()->json(['status' => 401, 'message' => "Users can upload only 10 posts a day!"]);
        }

        $is_orignal_sound = $request->get('is_orignal_sound');
        $post_video = '';
        $post_image = '';

        if ($request->hasfile('post_video')) {
            $file = $request->file('post_video');
            $post_video = GlobalFunction::uploadFilToS3($file);
        }

        if ($request->hasfile('post_image')) {
            $file = $request->file('post_image');
            $post_image = GlobalFunction::uploadFilToS3($file);
        }

        $data['post_description'] = $request->get('post_description') ? $request->get('post_description') : "";
        $data['post_hash_tag'] = $request->get('post_hash_tag') ? $request->get('post_hash_tag') : "";
        $data['user_id'] = $user_id;
        $data['post_video'] = $post_video;
        $data['post_image'] = $post_image;
        $data['can_comment'] = $request->get('can_comment') ? $request->get('can_comment') : 0;
        $data['can_duet'] = $request->get('can_duet') ? $request->get('can_duet') : 0;
        $data['can_save'] = $request->get('can_save') ? $request->get('can_save') : 0;

        $insert_post = Post::insert($data);
        $post_id = DB::getPdo()->lastInsertId();

        if ($insert_post) {
            $post_hash_tag = $request->get('post_hash_tag');
            if (!empty($post_hash_tag)) {
                $hash_tag_array = explode(",", $post_hash_tag);
                foreach ($hash_tag_array as $value) {
                    $count = HashTags::where('hash_tag_name', $value)->count();
                    if ($count == 0) {
                        $data1['hash_tag_name'] = $value;
                        $insert_hash = HashTags::insert($data1);
                    }
                }
            }

            if ($is_orignal_sound == 1) {
                $post_sound = '';
                $sound_image = '';
                if ($request->hasfile('post_sound')) {
                    $file = $request->file('post_sound');
                    $post_sound = GlobalFunction::uploadFilToS3($file);
                }

                if ($request->hasfile('sound_image')) {
                    $file = $request->file('sound_image');
                    $sound_image = GlobalFunction::uploadFilToS3($file);
                }

                $data2['sound'] = $post_sound;
                $data2['sound_title'] = $request->get('sound_title');
                $data2['duration'] = $request->get('duration');
                $data2['singer'] = $request->get('singer');
                $data2['sound_image'] = $sound_image;

                $insert_sound = Sound::insert($data2);
                $sound_id = DB::getPdo()->lastInsertId();
            } else if ($is_orignal_sound == 0) {
                $sound_id = $request->get('sound_id');
            }

            $data3['sound_id'] = $sound_id;
            Post::where('post_id', $post_id)->update($data3);

            return response()->json(['status' => 200, 'message' => "Post Added Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Add Post."]);
        }
    }

    public function deletePost(Request $request)
    {


        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $post_id = $request->get('post_id');

        if ($post_id) {
            $checkPost =  Post::where('post_id', $post_id)->first();
            if (empty($checkPost)) {
                return response()->json(['status' => 401, 'message' => "Post Not Found"]);
            }
        }

        $delete_post = Post::where('post_id', $post_id)->delete();
        Like::where('post_id', $post_id)->delete();
        comments::where('post_id', $post_id)->delete();
        Bookmark::where('post_id', $post_id)->delete();
        Report::where('post_id', $post_id)->delete();
        Notification::where('item_id', $post_id)->delete();
        if ($delete_post) {
            return response()->json(['status' => 200, 'message' => "Post Delete Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Delete Post."]);
        }
    }

    public function getPostList(Request $request)
    {



        // $user_id = $request->user()->user_id;
        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'limit' => 'required',
            'type' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $type = $request->get('type');
        $user_id = $request->get('user_id');

        if ($type == "following") {

            $followersData = Followers::select(DB::raw("group_concat(to_user_id SEPARATOR ',')  as to_user_ids"))
                ->where('from_user_id', $user_id)
                ->first();

            $followers_id = $followersData['to_user_ids'];
            $followers_id = explode(',', $followers_id);

            $query = Post::select('*');
            foreach ($followers_id as $val) {
                $query->orWhere('user_id', $val);
            }
            $post_list = $query->inRandomOrder()->limit($limit)->get();
        } else if ($type == "trending") {
            $post_list = Post::where('is_trending', 1)->inRandomOrder()->limit($limit)->get();
        } else {
            $post_list = Post::inRandomOrder()->inRandomOrder()->limit($limit)->get();
        }

        $i = 0;
        $postData = [];
        if (count($post_list) > 0) {

            foreach ($post_list as $post_data_value) {

                $post_userdata  = BlockUser::where('block_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                if (empty($post_userdata)) {
                    $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                    $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                    $soundData  = Sound::where('sound_id', $post_data_value['sound_id'])->first();
                    $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                    $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                    $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $post_data_value['user_id'])->first();

                    $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                    $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                    $postData[$i]['full_name'] = $userData['full_name'];
                    $postData[$i]['user_name'] = $userData['user_name'];
                    $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";;
                    $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                    $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                    $postData[$i]['post_description'] = $post_data_value['post_description'];
                    $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                    $postData[$i]['post_video'] = $post_data_value['post_video'];
                    $postData[$i]['post_image'] = $post_data_value['post_image'];
                    $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                    $postData[$i]['sound_title'] = $soundData['sound_title'];
                    $postData[$i]['duration'] = $soundData['duration'];
                    $postData[$i]['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
                    $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                    $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                    $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                    $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                    $postData[$i]['post_likes_count'] = (int)$post_likes_count;
                    $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                    $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                    $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                    $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                    $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                    $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                    $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                    $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                    $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;
                    $i++;
                }
            }

            return response()->json(['status' => 200, 'message' => "Post List Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $postData]);
        }
    }

    public function getCommentByPostId(Request $request)
    {


        // $user_id = $request->user()->user_id;
        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $post_id = $request->get('post_id');
        $user_id = $request->get('user_id');

        $comments_list = Comments::where('post_id', $post_id)->orderBy('comments_id', 'DESC')->offset($start)->limit($limit)->get();

        $c = 0;
        $commentData = [];
        if (count($comments_list) > 0) {
            foreach ($comments_list as $comment_value) {
                $userData = User::where('user_id', $comment_value['user_id'])->first();

                $commentData[$c]['comments_id'] = (int)$comment_value['comments_id'];
                $commentData[$c]['comment'] = $comment_value['comment'];
                $commentData[$c]['created_date'] = date('Y-m-d h:i:s', strtotime($comment_value['created_at']));
                $commentData[$c]['user_id'] = (int)$comment_value['user_id'];
                $commentData[$c]['full_name'] = $userData['full_name'];
                $commentData[$c]['user_name'] = $userData['user_name'];
                $commentData[$c]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                $commentData[$c]['is_verify'] = (int)$userData['is_verify'];
                $c++;
            }

            return response()->json(['status' => 200, 'message' => "Post List Get Successfully.", 'data' => $commentData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $commentData]);
        }
    }

    public function LikeUnlikePost(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $post_id = $request->get('post_id');
        $full_name = $request->user()->full_name;

        $checkPost =  Post::where('post_id', $post_id)->first();
        if (empty($checkPost)) {
            return response()->json(['status' => 401, 'message' => "Post Not Found"]);
        }

        $countLike = Like::where('user_id', $user_id)->where('post_id', $post_id)->count();

        if ($countLike > 0) {

            $delete = Like::where('user_id', $user_id)->where('post_id', $post_id)->delete();
            return response()->json(['status' => 200, 'message' => "Post Unlike Successful"]);
        } else {

            $data = array('post_id' => $post_id, 'user_id' => $user_id);
            $insert =  Like::insert($data);

            $postData =  post::where('post_id', $post_id)->first();
            $noti_user_id = $postData['user_id'];
            $userData =  User::where('user_id', $noti_user_id)->first();
            $platform = $userData['platform'];
            $device_token = $userData['device_token'];
            $message = $full_name . ' liked your video';

            if ($user_id != $noti_user_id) {
                $notificationdata = array(
                    'sender_user_id' => $user_id,
                    'received_user_id' => $noti_user_id,
                    'notification_type' => 1,
                    'item_id' => $post_id,
                    'message' => $message,
                );

                Notification::insert($notificationdata);
                $notification_title = "Shortzz";
                if($userData->is_notification == 1 ){
                $is_send = Common::send_push($device_token, $notification_title, $message, $platform);
                }
            }

            return response()->json(['status' => 200, 'message' => "Post Like Successful."]);
        }
    }



    public function FollowUnfollowPost(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'to_user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $to_user_id = $request->get('to_user_id');
        $full_name = $request->user()->full_name;

        $checkUser =  User::where('user_id', $to_user_id)->first();
        if (empty($checkUser)) {
            return response()->json(['status' => 401, 'message' => "User Not Found"]);
        }

        $countFollowers = Followers::where('from_user_id', $user_id)->where('to_user_id', $to_user_id)->count();

        if ($countFollowers > 0) {

            $delete = Followers::where('from_user_id', $user_id)->where('to_user_id', $to_user_id)->delete();
            return response()->json(['status' => 200, 'message' => "Unfollow Successful"]);
        } else {

            $data = array('to_user_id' => $to_user_id, 'from_user_id' => $user_id);
            $insert =  Followers::insert($data);

            $userData =  User::where('user_id', $to_user_id)->first();
            $platform = $userData['platform'];
            $device_token = $userData['device_token'];
            $message = $full_name . ' started following you';


            $notificationdata = array(
                'sender_user_id' => $user_id,
                'received_user_id' => $to_user_id,
                'notification_type' => 3,
                'item_id' => $user_id,
                'message' => $message,
            );

            Notification::insert($notificationdata);
            $notification_title = "Shortzz";
            if($userData->is_notification == 1 ){
            Common::send_push($device_token, $notification_title, $message, $platform);
            }

            return response()->json(['status' => 200, 'message' => "Follow Successful."]);
        }
    }

    public function getFollowerList(Request $request)
    {


        // $user_id = $request->user()->user_id;
        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $user_id = $request->get('user_id');

        $followers_list = Followers::where('to_user_id', $user_id)->orderBy('follower_id', 'DESC')->offset($start)->limit($limit)->get();

        $i = 0;
        $followerData = [];
        if (count($followers_list) > 0) {
            foreach ($followers_list as $follower_value) {
                $userData = User::where('user_id', $follower_value['from_user_id'])->first();
                $followers_count = Followers::where('to_user_id', $follower_value['from_user_id'])->count();
                $following_count = Followers::where('from_user_id', $follower_value['from_user_id'])->count();

                $my_post_likes = Post::select('tbl_post.*')->leftjoin('tbl_likes as l', 'l.post_id', 'tbl_post.post_id')->where('tbl_post.user_id', $follower_value['from_user_id'])->count();
                $my_post_count = Post::where('user_id', $follower_value['from_user_id'])->count();

                $followerData[$i]['follower_id'] = (int)$follower_value['follower_id'];
                $followerData[$i]['from_user_id'] = (int)$follower_value['from_user_id'];
                $followerData[$i]['to_user_id'] = (int)$follower_value['to_user_id'];
                $followerData[$i]['full_name'] = $userData['full_name'];
                $followerData[$i]['user_name'] = $userData['user_name'];
                $followerData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                $followerData[$i]['is_verify'] = (int)$userData['is_verify'];
                $followerData[$i]['created_date'] =  date('Y-m-d h:i:s', strtotime($follower_value['created_at']));
                $followerData[$i]['followers_count'] = (int)$followers_count;
                $followerData[$i]['following_count'] = (int)$following_count;
                $followerData[$i]['my_post_likes'] = (int)$my_post_likes;
                $followerData[$i]['my_post_count'] = (int)$my_post_count;
                $i++;
            }

            return response()->json(['status' => 200, 'message' => "Post List Get Successfully.", 'data' => $followerData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $followerData]);
        }
    }

    public function getFollowingList(Request $request)
    {


        // $user_id = $request->user()->user_id;
        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $user_id = $request->get('user_id');

        $followers_list = Followers::where('from_user_id', $user_id)->orderBy('follower_id', 'DESC')->offset($start)->limit($limit)->get();

        $i = 0;
        $followerData = [];
        if (count($followers_list) > 0) {
            foreach ($followers_list as $follower_value) {
                $userData = User::where('user_id', $follower_value['to_user_id'])->first();
                $followers_count = Followers::where('to_user_id', $follower_value['to_user_id'])->count();
                $following_count = Followers::where('from_user_id', $follower_value['to_user_id'])->count();

                $my_post_likes = Post::select('tbl_post.*')->leftjoin('tbl_likes as l', 'l.post_id', 'tbl_post.post_id')->where('tbl_post.user_id', $follower_value['to_user_id'])->count();
                $my_post_count = Post::where('user_id', $follower_value['to_user_id'])->count();

                $followerData[$i]['follower_id'] = (int)$follower_value['follower_id'];
                $followerData[$i]['from_user_id'] = (int)$follower_value['from_user_id'];
                $followerData[$i]['to_user_id'] = (int)$follower_value['to_user_id'];
                $followerData[$i]['full_name'] = $userData['full_name'];
                $followerData[$i]['user_name'] = $userData['user_name'];
                $followerData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                $followerData[$i]['is_verify'] = (int)$userData['is_verify'];
                $followerData[$i]['created_date'] =  date('Y-m-d h:i:s', strtotime($follower_value['created_at']));
                $followerData[$i]['followers_count'] = (int)$followers_count;
                $followerData[$i]['following_count'] = (int)$following_count;
                $followerData[$i]['my_post_likes'] = (int)$my_post_likes;
                $followerData[$i]['my_post_count'] = (int)$my_post_count;
                $i++;
            }

            return response()->json(['status' => 200, 'message' => "Post List Get Successfully.", 'data' => $followerData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $followerData]);
        }
    }

    public function getSoundList(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $checkUser =  User::where('user_id', $user_id)->first();
        if (empty($checkUser)) {
            return response()->json(['status' => 401, 'message' => "User Not Found"]);
        }

        $sound_category_list = SoundCategory::where('is_deleted', 0)->orderBy('sound_category_id', 'DESC')->get();

        $i = 0;
        $soundCategoryData = [];
        if (count($sound_category_list) > 0) {
            foreach ($sound_category_list as $catvalue) {

                $sound_list = Sound::where('is_deleted', 0)->where('sound_category_id', $catvalue['sound_category_id'])->orderBy('sound_category_id', 'DESC')->get();

                $soundCategoryData[$i]['sound_category_id'] = (int)$catvalue['sound_category_id'];
                $soundCategoryData[$i]['sound_category_name'] = $catvalue['sound_category_name'];
                $soundCategoryData[$i]['sound_category_profile'] = $catvalue['sound_category_profile'] ? $catvalue['sound_category_profile'] : "";
                $soundCategoryData[$i]['sound_list'] = $sound_list;
                $i++;
            }

            return response()->json(['status' => 200, 'message' => "Sound List Get Successfully.", 'data' => $soundCategoryData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $soundCategoryData]);
        }
    }

    public function getSoundByCategoryId(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'sound_category_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $sound_category_id = $request->get('sound_category_id');

        $checkUser =  User::where('user_id', $user_id)->first();
        if (empty($checkUser)) {
            return response()->json(['status' => 401, 'message' => "User Not Found"]);
        }

        $soundList = Sound::where('sound_category_id', $sound_category_id)->orderBy('sound_category_id', 'DESC')->get();

        if (count($soundList) > 0) {
            return response()->json(['status' => 200, 'message' => "Category Wise Sound List Get Successfully.", 'data' => $soundList]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $soundList]);
        }
    }

    public function getSearchSoundList(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $keyword = $request->get('keyword');

        if ($keyword) {
            $soundList = Sound::where('is_deleted', 0)
            ->where('added_by', 'admin')
            ->where('sound_title', 'LIKE', "%{$keyword}%")
            ->orderBy('sound_id', 'DESC')->get();
        } else {
            $soundList = Sound::where('is_deleted', 0)->where('added_by', 'admin')->orderBy('sound_id', 'DESC')->get();
        }

        if (count($soundList) > 0) {
            return response()->json(['status' => 200, 'message' => "Search Sound List Get Successfully.", 'data' => $soundList]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $soundList]);
        }
    }

    public function getUserSearchPostList(Request $request)
    {



        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $keyword = $request->get('keyword');
        $user_id = $request->get('user_id');

        if ($keyword) {
            $user_list = User::where('full_name', 'LIKE', "%{$keyword}%")->orWhere('user_name', 'LIKE', "%{$keyword}%")->orderBy('user_id', 'DESC')->offset($start)->limit($limit)->get();
        } else {
            $user_list = User::orderBy('user_id', 'DESC')->offset($start)->limit($limit)->get();
        }

        $i = 0;
        $userData = [];
        if (count($user_list) > 0) {

            foreach ($user_list as $user_value) {

                $followers_count = Followers::where('to_user_id', $user_value['user_id'])->count();
                $following_count = Followers::where('from_user_id', $user_value['user_id'])->count();

                $my_post_likes = Post::select('tbl_post.*')->leftjoin('tbl_likes as l', 'l.post_id', 'tbl_post.post_id')->where('tbl_post.user_id', $user_value['user_id'])->count();
                $my_post_count = Post::where('user_id', $user_value['user_id'])->count();

                $userData[$i]['user_id'] = (int)$user_value['user_id'];
                $userData[$i]['full_name'] = $user_value['full_name'];
                $userData[$i]['user_name'] = $user_value['user_name'];
                $userData[$i]['user_email'] = $user_value['user_email'];
                $userData[$i]['user_mobile_no'] = $user_value['user_mobile_no'] ? $user_value['user_mobile_no'] : "";
                $userData[$i]['user_profile'] = $user_value['user_profile'] ? $user_value['user_profile'] : "";
                $userData[$i]['is_verify'] = (int)$user_value['is_verify'];
                $userData[$i]['bio'] = $user_value['bio'] ? $user_value['bio'] : "";
                $userData[$i]['fb_url'] = $user_value['fb_url'] ? $user_value['fb_url'] : "";
                $userData[$i]['insta_url'] = $user_value['insta_url'] ? $user_value['insta_url'] : "";
                $userData[$i]['youtube_url'] = $user_value['youtube_url'] ? $user_value['youtube_url'] : "";
                $userData[$i]['followers_count'] = (int)$followers_count;
                $userData[$i]['following_count'] = (int)$following_count;
                $userData[$i]['my_post_likes'] = (int)$my_post_likes;
                $userData[$i]['my_post_count'] = (int)$my_post_count;
                $i++;
            }

            return response()->json(['status' => 200, 'message' => "Post List Get Successfully.", 'data' => $userData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $userData]);
        }
    }

    public function getSearchPostList(Request $request)
    {


        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $keyword = $request->get('keyword');
        $user_id = $request->get('user_id');

        // if($keyword){
        //     $post_search_video = Post::where('post_hash_tag','LIKE',"%{$keyword}%")->orWhere('post_description','LIKE',"%{$keyword}%")->orderBy('post_id','DESC')->offset($start)->limit($limit)->get();
        // }else{
        //     $post_search_video = Post::orderBy('post_id','DESC')->offset($start)->limit($limit)->get();
        // }
        if ($keyword) {
            $post_search_video = Post::where('post_hash_tag', 'LIKE', "%{$keyword}%")->orWhere('post_description', 'LIKE', "%{$keyword}%")->orderBy('post_id', 'DESC')->get();
        } else {
            $post_search_video = Post::orderBy('post_id', 'DESC')->get();
        }

        $i = 0;
        $postData = [];
        if (count($post_search_video) > 0) {

            foreach ($post_search_video as $post_data_value) {

                $post_userdata  = BlockUser::where('block_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                if (empty($post_userdata)) {

                    $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                    $soundData  = Sound::where('sound_id', $post_data_value['sound_id'])->first();
                    $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                    $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                    $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                    $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

                    $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                    $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                    $postData[$i]['full_name'] = $userData['full_name'];
                    $postData[$i]['user_name'] = $userData['user_name'];
                    $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                    $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                    $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                    $postData[$i]['post_description'] = $post_data_value['post_description'];
                    $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                    $postData[$i]['post_video'] = $post_data_value['post_video'];
                    $postData[$i]['post_image'] = $post_data_value['post_image'];
                    $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                    $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                    $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                    $postData[$i]['sound_title'] = $soundData['sound_title'];
                    $postData[$i]['duration'] = $soundData['duration'];
                    $postData[$i]['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
                    $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                    $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                    $postData[$i]['post_likes_count'] = (int)$post_likes_count;
                    $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                    $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                    $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                    $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                    $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                    $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                    $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                    $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                    $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;

                    $i++;
                }
            }


            // $post_likes_count = array_column($postData, 'post_likes_count');

            // array_multisort($post_likes_count, SORT_DESC, $postData);

            $postData = array_slice($postData, $start, $limit);

            return response()->json(['status' => 200, 'message' => "User Videos Data Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $postData]);
        }
    }

    public function getExploreHashTagPostList(Request $request)
    {



        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $user_id = $request->get('user_id');

        $explore_hash_tag = HashTags::where('move_explore', 1)->orderBy('hash_tag_id', 'DESC')->offset($start)->limit($limit)->get();

        $i = 0;
        $postData = [];
        if (count($explore_hash_tag) > 0) {

            foreach ($explore_hash_tag as $post_data_value) {
                $hash_tag_videos_count = Post::where('post_hash_tag', 'LIKE', "%{$post_data_value['hash_tag_name']}%")->count();

                $postData[$i]['hash_tag_name'] = $post_data_value['hash_tag_name'];
                $postData[$i]['hash_tag_profile'] = $post_data_value['hash_tag_profile'] ? $post_data_value['hash_tag_profile'] : "";
                $postData[$i]['hash_tag_videos_count'] = (int)$hash_tag_videos_count;

                $i++;
            }
            return response()->json(['status' => 200, 'message' => "Explore Hash Tag Videos Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $postData]);
        }
    }

    public function getSingleHashTagPostList(Request $request)
    {



        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
            'hash_tag' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $hash_tag = $request->get('hash_tag');
        $user_id = $request->get('user_id');

        $hash_tag_videos = Post::where('post_hash_tag', 'LIKE', "%{$hash_tag}%")->orderBy('post_id', 'DESC')->offset($start)->limit($limit)->get();
        $post_count = Post::where('post_hash_tag', 'LIKE', "%{$hash_tag}%")->count();
        $totalpostData = $post_count;

        $i = 0;
        $postData = [];
        if (count($hash_tag_videos) > 0) {

            foreach ($hash_tag_videos as $post_data_value) {

                $post_userdata  = BlockUser::where('block_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                if (empty($post_userdata)) {

                    $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                    $soundData  = Sound::where('sound_id', $post_data_value['sound_id'])->first();
                    $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                    $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                    $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                    $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

                    $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                    $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                    $postData[$i]['full_name'] = $userData['full_name'];
                    $postData[$i]['user_name'] = $userData['user_name'];
                    $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                    $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                    $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                    $postData[$i]['post_description'] = $post_data_value['post_description'];
                    $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                    $postData[$i]['post_video'] = $post_data_value['post_video'];
                    $postData[$i]['post_image'] = $post_data_value['post_image'];
                    $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                    $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                    $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                    $postData[$i]['sound_title'] = $soundData['sound_title'];
                    $postData[$i]['duration'] = $soundData['duration'];
                    $postData[$i]['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
                    $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                    $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                    $postData[$i]['post_likes_count'] = $post_likes_count;
                    $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                    $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                    $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                    $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                    $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                    $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                    $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                    $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                    $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;

                    $i++;
                }
            }
            // $post_likes_count = array_column($postData, 'post_likes_count');

            // array_multisort($post_likes_count, SORT_DESC, $postData);

            return response()->json(['status' => 200, 'message' => "Hash Tag Wise Videos Data Get Successfully.", 'total_videos' => $totalpostData, 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'total_videos' => $totalpostData, 'data' => $postData]);
        }
    }

    public function IncreasePostViewCount(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $post_id = $request->get('post_id');

        $checkPost =  Post::where('post_id', $post_id)->first();
        if (empty($checkPost)) {
            return response()->json(['status' => 401, 'message' => "Post Not Found"]);
        }
        Post::where('post_id', $post_id)->increment('video_view_count');
        return response()->json(['status' => 200, 'message' => "Videos Views Update Successful"]);
    }

    public function addComment(Request $request)
    {


        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'post_id' => 'required',
            'comment' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $post_id = $request->get('post_id');
        $comment = $request->get('comment');
        $full_name = $request->user()->full_name;

        $checkPost =  Post::where('post_id', $post_id)->first();
        if (empty($checkPost)) {
            return response()->json(['status' => 401, 'message' => "Post Not Found"]);
        }

        $data['post_id'] = $post_id;
        $data['user_id'] = $user_id;
        $data['comment'] = $comment;

        $insert_comment = Comments::insert($data);

        if ($insert_comment) {

            $postData =  post::where('post_id', $post_id)->first();
            $noti_user_id = $postData['user_id'];
            $userData =  User::where('user_id', $noti_user_id)->first();
            $platform = $userData['platform'];
            $device_token = $userData['device_token'];
            $message = $full_name . ' commented on your video: ' . $comment;

            if ($user_id != $noti_user_id) {
                $notificationdata = array(
                    'sender_user_id' => $user_id,
                    'received_user_id' => $noti_user_id,
                    'notification_type' => 2,
                    'item_id' => $post_id,
                    'message' => $message,
                );

                Notification::insert($notificationdata);
                $notification_title = "Shortzz";
                if($userData->is_notification == 1 ){
               Common::send_push($device_token, $notification_title, $message, $platform);
                }
            }

            return response()->json(['status' => 200, 'message' => "Comment Added Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Add Comment."]);
        }
    }

    public function deleteComment(Request $request)
    {


        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'comments_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $comments_id = $request->get('comments_id');

        $delete = Comments::where('comments_id', $comments_id)->delete();

        if ($delete) {
            return response()->json(['status' => 200, 'message' => "Comment Delete Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Delete Comment."]);
        }
    }

    public function ReportPost(Request $request)
    {


        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'report_type' => 'required',
            // 'post_id' => 'required',
            'reason' => 'required',
            'description' => 'required',
            'contact_info' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $post_id = $request->get('post_id') ?  $request->get('post_id') : 0;
        $report_type = $request->get('report_type');
        $reason = $request->get('reason');
        $description = $request->get('description');
        $contact_info = $request->get('contact_info');
        $user_id = $request->get('user_id');

        if ($post_id) {
            $checkPost =  Post::where('post_id', $post_id)->first();
            if (empty($checkPost)) {
                return response()->json(['status' => 401, 'message' => "Post Not Found"]);
            }
        }

        $data['post_id'] = (int)$post_id;
        $data['user_id'] = (int)$user_id;
        $data['report_type'] = $report_type;
        $data['reason'] = $reason;
        $data['description'] = $description;
        $data['contact_info'] = $contact_info;

        $insert_comment = Report::insert($data);

        if ($insert_comment) {
            return response()->json(['status' => 200, 'message' => "Report Added Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Add Report."]);
        }
    }

    public function getFavouriteSoundList(Request $request)
    {


        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        // $rules = [
        //     'sound_ids[]' => 'required',
        // ];

        // $validator = Validator::make($request->all(), $rules);

        // if ($validator->fails()) {
        //     $messages = $validator->errors()->all();
        //     $msg = $messages[0];
        //     return response()->json(['status' => 401, 'message' => $msg]);
        // }
        $sound_ids = $request->get('sound_ids');
        $soundData =  [];

        $soundData =  Sound::where('is_deleted', 0)->whereIn('sound_id', $sound_ids)->get();

        if (count($soundData) > 0) {
            return response()->json(['status' => 200, 'message' => "Favourite Sound List Get Successfully.", 'data' => $soundData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $soundData]);
        }
    }

    public function bookMarkedPost(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $post_id = $request->get('post_id');
        $full_name = $request->user()->full_name;

        $checkPost =  Post::where('post_id', $post_id)->first();
        if (empty($checkPost)) {
            return response()->json(['status' => 401, 'message' => "Post Not Found"]);
        }

        $countBookmark = Bookmark::where('user_id', $user_id)->where('post_id', $post_id)->count();

        if ($countBookmark > 0) {
            return response()->json(['status' => 200, 'message' => "Post Already Bookmark"]);
        } else {
            $data = array('post_id' => $post_id, 'user_id' => $user_id);
            $insert =  Bookmark::insert($data);
            return response()->json(['status' => 200, 'message' => "Bookmark Post Successful."]);
        }
    }

    public function getBookmarkPostList(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;

        $post_list = Bookmark::select('p.*')->leftjoin('tbl_post as p', 'p.post_id', 'tbl_bookmark.post_id')->where('tbl_bookmark.user_id', $user_id)->orderBy('p.post_id', 'DESC')->offset($start)->limit($limit)->get();

        $i = 0;
        $postData = [];
        if (count($post_list) > 0) {

            foreach ($post_list as $post_data_value) {

                $post_userdata  = BlockUser::where('block_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                if (empty($post_userdata)) {
                    $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                    $soundData  = Sound::where('sound_id', $post_data_value['sound_id'])->first();
                    $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                    $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                    $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                    $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

                    $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                    $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                    $postData[$i]['full_name'] = $userData['full_name'];
                    $postData[$i]['user_name'] = $userData['user_name'];
                    $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                    $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                    $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                    $postData[$i]['post_description'] = $post_data_value['post_description'];
                    $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                    $postData[$i]['post_video'] = $post_data_value['post_video'];
                    $postData[$i]['post_image'] = $post_data_value['post_image'];
                    $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                    $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                    $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                    $postData[$i]['sound_title'] = $soundData['sound_title'];
                    $postData[$i]['duration'] = $soundData['duration'];
                    $postData[$i]['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
                    $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                    $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                    $postData[$i]['post_likes_count'] = (int)$post_likes_count;
                    $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                    $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                    $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                    $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                    $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                    $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                    $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                    $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                    $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;

                    $i++;
                }
            }
            // $post_likes_count = array_column($postData, 'post_likes_count');

            // array_multisort($post_likes_count, SORT_DESC, $postData);
            return response()->json(['status' => 200, 'message' => "Bookmarked Post Data Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $postData]);
        }
    }

    public function getPostListById(Request $request)
    {


        // $user_id = $request->user()->user_id;
        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'post_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $post_id = $request->get('post_id');
        $user_id = $request->get('user_id');

        $post_list = Post::where('post_id', $post_id)->inRandomOrder()->first();

        $postData = [];
        if (!empty($post_list)) {
            $userData  = User::where('user_id', $post_list['user_id'])->first();
            $soundData  = Sound::where('sound_id', $post_list['sound_id'])->first();
            $post_comments_count  = Comments::where('post_id', $post_list['post_id'])->count();
            $post_likes_count  = Like::where('post_id', $post_list['post_id'])->count();
            $is_video_like  = Like::where('post_id', $post_list['post_id'])->where('user_id', $user_id)->first();
            $follow_or_not  = Followers::where('to_user_id', $post_list['user_id'])->where('from_user_id', $user_id)->first();
            $is_bookmark  = Bookmark::where('post_id', $post_list['post_id'])->where('user_id', $user_id)->first();
            $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

            $postData['post_id'] = (int)$post_list['post_id'];
            $postData['user_id'] = (int)$post_list['user_id'];
            $postData['full_name'] = $userData['full_name'];
            $postData['user_name'] = $userData['user_name'];
            $postData['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";;
            $postData['is_verify'] = (int)$userData['is_verify'];
            $postData['is_trending'] = (int)$post_list['is_trending'];
            $postData['post_description'] = $post_list['post_description'];
            $postData['post_hash_tag'] = $post_list['post_hash_tag'];
            $postData['post_video'] = $post_list['post_video'];
            $postData['post_image'] = $post_list['post_image'];
            $postData['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
            $postData['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
            $postData['sound_id'] = (int)$soundData['sound_id'];
            $postData['sound_title'] = $soundData['sound_title'];
            $postData['duration'] = $soundData['duration'];
            $postData['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
            $postData['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";;
            $postData['sound'] = $soundData['sound'] ? $soundData['sound'] : "";;
            $postData['post_likes_count'] = (int)$post_likes_count;
            $postData['post_comments_count'] = (int)$post_comments_count;
            $postData['post_view_count'] = (int)$post_list['video_view_count'];
            $postData['created_date'] = date('Y-m-d h:i:s', strtotime($post_list['created_at']));
            $postData['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
            $postData['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
            $postData['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
            $postData['can_comment'] = $post_list['can_comment'] ? 1 : 0;
            $postData['can_duet'] = $post_list['can_duet'] ? 1 : 0;
            $postData['can_save'] = $post_list['can_save'] ?  1 : 0;

            return response()->json(['status' => 200, 'message' => "Post Data Get Successfully.", 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found."]);
        }
    }

    public function getPostBySoundId(Request $request)
    {



        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
            'sound_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;
        $sound_id = $request->get('sound_id');
        $user_id = $request->get('user_id');

        $post_list = Sound::select('p.*')->leftjoin('tbl_post as p', 'p.sound_id', 'tbl_sound.sound_id')->where('tbl_sound.sound_id', $sound_id)->orderBy('p.post_id', 'DESC')->offset($start)->limit($limit)->get();
        $totalpostData = Post::where('sound_id', $sound_id)->count();

        $i = 0;
        $postData = [];
        if (count($post_list) > 0) {

            foreach ($post_list as $post_data_value) {

                $post_userdata  = BlockUser::where('block_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                if (empty($post_userdata)) {

                    $userData  = User::where('user_id', $post_data_value['user_id'])->first();
                    $soundData  = Sound::where('sound_id', $sound_id)->first();
                    $post_video_count = Post::where('sound_id', $sound_id)->count();
                    $post_comments_count  = Comments::where('post_id', $post_data_value['post_id'])->count();
                    $post_likes_count  = Like::where('post_id', $post_data_value['post_id'])->count();
                    $is_video_like  = Like::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $follow_or_not  = Followers::where('to_user_id', $post_data_value['user_id'])->where('from_user_id', $user_id)->first();
                    $is_bookmark  = Bookmark::where('post_id', $post_data_value['post_id'])->where('user_id', $user_id)->first();
                    $profile_category_data = ProfileCategory::select('tbl_profile_category.*')->leftjoin('tbl_users as u', 'u.profile_category', 'tbl_profile_category.profile_category_id')->where('u.user_id', $user_id)->first();

                    $postData[$i]['post_id'] = (int)$post_data_value['post_id'];
                    $postData[$i]['user_id'] = (int)$post_data_value['user_id'];
                    $postData[$i]['full_name'] = $userData['full_name'];
                    $postData[$i]['user_name'] = $userData['user_name'];
                    $postData[$i]['user_profile'] = $userData['user_profile'] ? $userData['user_profile'] : "";
                    $postData[$i]['is_verify'] = (int)$userData['is_verify'];
                    $postData[$i]['is_trending'] = (int)$post_data_value['is_trending'];
                    $postData[$i]['post_description'] = $post_data_value['post_description'];
                    $postData[$i]['post_hash_tag'] = $post_data_value['post_hash_tag'];
                    $postData[$i]['post_video'] = $post_data_value['post_video'];
                    $postData[$i]['post_image'] = $post_data_value['post_image'];
                    $postData[$i]['profile_category_id'] = ($profile_category_data && $profile_category_data['profile_category_id']) ? (int)$profile_category_data['profile_category_id'] : "";
                    $postData[$i]['profile_category_name'] = ($profile_category_data && $profile_category_data['profile_category_name']) ? $profile_category_data['profile_category_name'] : "";
                    $postData[$i]['sound_id'] = (int)$soundData['sound_id'];
                    $postData[$i]['sound_title'] = $soundData['sound_title'];
                    $postData[$i]['duration'] = $soundData['duration'];
                    $postData[$i]['singer'] = $soundData['singer'] ? $soundData['singer'] : "";
                    $postData[$i]['sound_image'] = $soundData['sound_image'] ? $soundData['sound_image'] : "";
                    $postData[$i]['sound'] = $soundData['sound'] ? $soundData['sound'] : "";
                    $postData[$i]['post_video_count'] = $post_video_count;
                    $postData[$i]['post_likes_count'] = $post_likes_count;
                    $postData[$i]['post_comments_count'] = (int)$post_comments_count;
                    $postData[$i]['post_view_count'] = (int)$post_data_value['video_view_count'];
                    $postData[$i]['created_date'] = date('Y-m-d h:i:s', strtotime($post_data_value['created_at']));
                    $postData[$i]['video_likes_or_not'] = !empty($is_video_like) ? 1 : 0;
                    $postData[$i]['follow_or_not'] = !empty($follow_or_not) ? 1 : 0;
                    $postData[$i]['is_bookmark'] = !empty($is_bookmark) ? 1 : 0;
                    $postData[$i]['can_comment'] = $post_data_value['can_comment'] ? 1 : 0;
                    $postData[$i]['can_duet'] = $post_data_value['can_duet'] ? 1 : 0;
                    $postData[$i]['can_save'] = $post_data_value['can_save'] ?  1 : 0;

                    $i++;
                }
            }
            // $post_likes_count = array_column($postData, 'post_likes_count');
            // array_multisort($post_likes_count, SORT_DESC, $postData);

            return response()->json(['status' => 200, 'message' => "Bookmarked Post Data Get Successfully.", 'total_videos' => $totalpostData, 'data' => $postData]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'total_videos' => $totalpostData, 'data' => $postData]);
        }
    }
}
