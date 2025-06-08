<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Redirect;
use URL;
use Session;
use Storage;
use App\Admin;
use App\User;
use App\Followers;
use App\Post;
use App\ProfileCategory;
use App\Like;
use App\Bookmark;
use App\Comments;
use App\Notification;
use App\Report;
use App\Common;
use App\GlobalFunction;

class UserController extends Controller
{

	public function viewListUser()
	{
		$total_user = User::count();
		return view('admin.user.user_list')->with('total_user', $total_user);
	}

	public function viewUser($id)
	{
		$user_id = base64_decode($id);
		$data = User::where('user_id', $user_id)->first();
		$followers_count = Followers::where('to_user_id', $user_id)->count();
		$following_count = Followers::where('from_user_id', $user_id)->count();
		$total_videos = Post::where('user_id', $user_id)->count();

		$profile_category_data = ProfileCategory::select('tbl_profile_category.*')
			->leftjoin('tbl_users as u', 'tbl_profile_category.profile_category_id', 'u.profile_category')
			->where('u.user_id', $user_id)
			->first();

		return view('admin.user.viewusers')->with('data', $data)->with('followers_count', $followers_count)->with('following_count', $following_count)->with('total_videos', $total_videos)->with('profile_category_data', $profile_category_data);
	}

	public function postUser($user_id)
	{
		$total_videos = Post::where('user_id', base64_decode($user_id))->count();
		return view('admin.user.user_post_list')->with('user_id', $user_id)->with('total_videos', $total_videos);
	}

    public function sendNotification(Request $request)
	{
		$message = $request->input('message');
		$user_id = $request->input('user_id');
		$userData = User::where('user_id', $user_id)->first();
		$platform = $userData['platform'];
		$device_token = $userData['device_token'];
		$notification_title = "Shortzz";

        if($userData->is_notification == 1){
		Common::send_push($device_token, $notification_title, $message, $platform);
    }
			$notificationdata = array(
				'sender_user_id' => 0,
				'received_user_id' => $user_id,
				'notification_type' => 5,
				'message' => $message,
			);

			Notification::insert($notificationdata);

			$response['success'] = 1;
			$response['message'] = "Successfully Send Notification";

		echo json_encode($response);
	}



	public function deleteUser(Request $request)
	{
		$user_id = $request->input('user_id');
		$result =  User::where('user_id', $user_id)->delete();
		$result =  Post::where('user_id', $user_id)->delete();

		Like::where('user_id', $user_id)->delete();
		comments::where('user_id', $user_id)->delete();
		Bookmark::where('user_id', $user_id)->delete();
		Report::where('user_id', $user_id)->delete();
		Notification::where('received_user_id', $user_id)->delete();

		$total_user = User::count();
		if ($result) {
			$response['success'] = 1;
			$response['total_user'] = $total_user;
		} else {
			$response['success'] = 0;
			$response['total_user'] = 0;
		}
		echo json_encode($response);
	}


	public function showUserList(Request $request)
	{

		$columns = array(
			0 => 'user_id',
			1 => 'full_name',
			2 => 'user_name',
			3 => 'user_email',
			4 => 'created_at',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		if (empty($request->input('search.value'))) {
			$UserData = User::offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();
			$totalData = $totalFiltered = User::count();
		} else {
			$search = $request->input('search.value');
			$UserData =  User::where('user_id', 'LIKE', "%{$search}%")
				->orWhere('full_name', 'LIKE', "%{$search}%")
				->orWhere('user_name', 'LIKE', "%{$search}%")
				->orWhere('user_email', 'LIKE', "%{$search}%")
				->orWhere('user_mobile_no', 'LIKE', "%{$search}%")
				->orWhere('identity', 'LIKE', "%{$search}%")
				->orWhere('fb_url', 'LIKE', "%{$search}%")
				->orWhere('insta_url', 'LIKE', "%{$search}%")
				->orWhere('youtube_url', 'LIKE', "%{$search}%")
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = User::where('user_id', 'LIKE', "%{$search}%")
				->orWhere('full_name', 'LIKE', "%{$search}%")
				->orWhere('user_name', 'LIKE', "%{$search}%")
				->orWhere('user_email', 'LIKE', "%{$search}%")
				->orWhere('user_mobile_no', 'LIKE', "%{$search}%")
				->orWhere('identity', 'LIKE', "%{$search}%")
				->orWhere('fb_url', 'LIKE', "%{$search}%")
				->orWhere('insta_url', 'LIKE', "%{$search}%")
				->orWhere('youtube_url', 'LIKE', "%{$search}%")
				->count();
		}

		$data = array();
		if (!empty($UserData)) {
			foreach ($UserData as $rows) {

				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}

				if ($rows->status == 0) {
					$status =  '<span class="badge badge-pill badge-danger">De-Active</span>';
				} elseif ($rows->status == 1) {
					$status =  '<span class="badge badge-pill badge-success">Active</span>';
				}


				if (!empty($rows->user_profile)) {
					$profile = '<img height="50px;" width="50px;" src="' . url(env('DEFAULT_IMAGE_URL') . $rows->user_profile) . '" class="" alt="">';
				} else {
					$profile = '<img height="50px;" width="50px;" src="' . asset('assets/dist/img/default.png') . '" class="" alt="">';
				}
				$view =  route('user/view', base64_encode($rows->user_id));
				$post =  route('user/post', base64_encode($rows->user_id));

				$data[] = array(
					$profile,
					$rows->full_name,
					$rows->user_name,
					$rows->user_email,
					date('Y-m-d', strtotime($rows->created_at)),
					$status,
					'<a href="' . $view . '" class="view"><i class="i-cl-3 fas fa-eye col-green  font-20 pointer p-l-5 p-r-5"></i></a>
					<a href="' . $post . '" class="video"><i class="i-cl-3 fas fa-video col-blue font-20 pointer p-l-5 p-r-5"></i></a>
					<a class="delete" id="userDelete" data-id="' . $rows->user_id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>',
				);
			}
		}

		$json_data = array(
			"draw"            => intval($request->input('draw')),
			"recordsTotal"    => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data"            => $data
		);

		echo json_encode($json_data);
		exit();
	}

	public function showUserPostList(Request $request)
	{
		$columns = array(
			0 => 'post_video',
			1 => 'post_image',
			2 => 'post_id',
			3 => 'post_description',
			4 => 'post_hash_tag',
			5 => 'video_view_count',
			6 => 'is_trending',
			7 => 'status',
			8 => 'created_at',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		$user_id = base64_decode($request->input('user_id'));

		if (empty($request->input('search.value'))) {

			$UserPostData = Post::select('tbl_post.*', 'u.full_name')->leftjoin('tbl_users as u', 'tbl_post.user_id', 'u.user_id')
				->where('tbl_post.user_id', $user_id)
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = Post::where('tbl_post.user_id', $user_id)->count();
		} else {
			$search = $request->input('search.value');
			$UserPostData = Post::select('tbl_post.*', 'u.full_name')->leftjoin('tbl_users as u', 'tbl_post.user_id', 'u.user_id')->where('tbl_post.post_id', 'LIKE', "%{$search}%")
				->orWhere('tbl_post.post_description', 'LIKE', "%{$search}%")
				->orWhere('tbl_post.post_hash_tag', 'LIKE', "%{$search}%")
				->orWhere('u.full_name', 'LIKE', "%{$search}%")
				->where('tbl_post.user_id', $user_id)
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = Post::select('tbl_post.*', 'u.full_name')->leftjoin('tbl_users as u', 'tbl_post.user_id', 'u.user_id')->where('tbl_post.post_id', 'LIKE', "%{$search}%")
				->orWhere('tbl_post.post_description', 'LIKE', "%{$search}%")
				->orWhere('tbl_post.post_hash_tag', 'LIKE', "%{$search}%")
				->orWhere('u.full_name', 'LIKE', "%{$search}%")
				->where('tbl_post.user_id', $user_id)
				->count();
		}

		$data = array();
		if (!empty($UserPostData)) {
			foreach ($UserPostData as $rows) {

				$status =  '<span class="badge badge-pill badge-success">Active</span>';

				if ($rows->is_trending == 0) {
					$is_trending =  '<span class="badge badge-pill badge-danger">No</span>';
				} elseif ($rows->is_trending == 1) {
					$is_trending =  '<span class="badge badge-pill badge-success">Yes</span>';
				}

				if (!empty($rows->post_image)) {
					$post_image = '<img height="50px;" width="50px;" src="' . url(env('DEFAULT_IMAGE_URL') . $rows->post_image) . '" class="" alt="">';
				} else {
					$post_image = '<img height="50px;" width="50px;" src="' . asset('assets/dist/img/default.png') . '" class="" alt="">';
				}

				if (!empty($rows->post_video)) {
					$post_video = '<button data-toggle="modal" data-target="#modal-video" data-src="' . url(env('DEFAULT_IMAGE_URL') . $rows->post_video) . '" class="btn btn-success text-white" id="playvideomdl" title="Play Video"><i class="fa fa-play" style="font-size: 14px;"></i></button>';
				} else {
					$post_video = '';
				}
				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}
				$data[] = array(
					$post_video,
					$post_image,
					$rows->full_name,
					$rows->post_description,
					$rows->post_hash_tag,
					$rows->video_view_count,
					$is_trending,
					$status,
					date('Y-m-d h:i:s', strtotime($rows->created_at)),
					'<a class="delete" id="postDelete" data-id="' . $rows->post_id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>
					'
				);
			}
		}

		$json_data = array(
			"draw"            => intval($request->input('draw')),
			"recordsTotal"    => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data"            => $data
		);

		echo json_encode($json_data);
		exit();
	}


	public function viewListProfileCategory()
	{
		$total_profile_category = ProfileCategory::count();
		return view('admin.user.profile_category_list')->with('total_profile_category', $total_profile_category);
	}

	public function addUpdateProfileCategory(Request $request)
	{
		$profile_category_id = $request->input('profile_category_id');
		$profile_category_name = $request->input('profile_category_name');

		if ($request->hasfile('profile_category_image')) {
			$file = $request->file('profile_category_image');
			$data['profile_category_image'] = GlobalFunction::uploadFilToS3($file);
		}

		$data['profile_category_name'] = $profile_category_name;

		if (!empty($profile_category_id)) {
			$result =  ProfileCategory::where('profile_category_id', $profile_category_id)->update($data);
			$msg = "Update";
			$response['flag'] = 2;
		} else {
			$result =  ProfileCategory::insert($data);
			$msg = "Add";
			$response['flag'] = 1;
		}
		$total_profile_category = ProfileCategory::count();
		if ($result) {
			$response['success'] = 1;
			$response['message'] = "Successfully " . $msg . " Profile";
			$response['total_profile_category'] = $total_profile_category;
		} else {
			$response['success'] = 0;
			$response['message'] = "Error While " . $msg . " Profile";
			$response['total_profile_category'] = 0;
		}
		echo json_encode($response);
	}

	public function deleteProfileCategory(Request $request)
	{

		$profile_category_id = $request->input('profile_category_id');
		$result =  ProfileCategory::where('profile_category_id', $profile_category_id)->delete();
		$total_profile_category = ProfileCategory::count();

		if ($result) {
			$response['success'] = 1;
			$response['total_profile_category'] = $total_profile_category;
		} else {
			$response['success'] = 0;
			$response['total_profile_category'] = 0;
		}
		echo json_encode($response);
	}

	public function showProfileCategoryList(Request $request)
	{

		$columns = array(
			0 => 'profile_category_id',
			1 => 'profile_category_name',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		if (empty($request->input('search.value'))) {

			$ProfileData = ProfileCategory::offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = ProfileCategory::count();
		} else {
			$search = $request->input('search.value');
			$ProfileData = ProfileCategory::where('profile_category_id', 'LIKE', "%{$search}%")
				->orWhere('profile_category_name', 'LIKE', "%{$search}%")
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = ProfileCategory::where('profile_category_id', 'LIKE', "%{$search}%")
				->orWhere('profile_category_name', 'LIKE', "%{$search}%")
				->count();
		}

		$data = array();
		if (!empty($ProfileData)) {
			foreach ($ProfileData as $rows) {

				if (!empty($rows->profile_category_image)) {
					$profile_category_image = '<img height="60" width="60" src="' . url(env('DEFAULT_IMAGE_URL') . $rows->profile_category_image) . '">';
				} else {
					$profile_category_image = '<img height="60px;" width="60px;" src="' . asset('assets/dist/img/default.png') . '">';
				}
				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}
				$data[] = array(
					$profile_category_image,
					$rows->profile_category_name,
					'<a class="UpdateProfileCategory" data-toggle="modal" data-target="#profileCategoryModal" data-id="' . $rows->profile_category_id . '" data-name="' . $rows->profile_category_name . '" data-src="' . url(env('DEFAULT_IMAGE_URL') . $rows->profile_category_image) . '" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>
					<a class="delete DeleteProfileCategory" data-id="' . $rows->profile_category_id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>'
				);
			}
		}
		$json_data = array(
			"draw"            => intval($request->input('draw')),
			"recordsTotal"    => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data"            => $data
		);

		echo json_encode($json_data);
		exit();
	}
}
