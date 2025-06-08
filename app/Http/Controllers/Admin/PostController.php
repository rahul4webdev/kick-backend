<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Redirect;
use URL;
use Hash;
use File;
use Session;
use DB;
use App\Admin;
use App\User;
use App\Post;
use App\Like;
use App\Bookmark;
use App\Comments;
use App\Notification;
use App\Report;

class PostController extends Controller
{

    public function viewListPost()
	{
		$total_videos = Post::count();
		$total_suggested = Post::where('is_trending',1)->count();
		return view('admin.post.post_list')->with('total_videos',$total_videos)->with('total_suggested',$total_suggested);
    }

	public function deletePost(Request $request){
		$post_id = $request->input('post_id');
		$result =  Post::where('post_id',$post_id)->delete();
		Like::where('post_id',$post_id)->delete();
		comments::where('post_id',$post_id)->delete();
		Bookmark::where('post_id',$post_id)->delete();
		Report::where('post_id',$post_id)->delete();
		Notification::where('item_id',$post_id)->delete();
		$total_videos = Post::count();
		$total_suggested = Post::where('is_trending',1)->count();
		if ($result) {
			$response['success'] = 1;
			$response['total_videos'] = $total_videos;
			$response['total_suggested'] = $total_suggested;
		} else {
			$response['success'] = 0;
			$response['total_videos'] = 0;
			$response['total_suggested'] = 0;
		}
		echo json_encode($response);
	}

	public function ChangeTrendingStatus(Request $request){
		$post_id = $request->input('post_id');
		$status = $request->input('status');
		$data['is_trending'] = $status;
		$result =  Post::where('post_id',$post_id)->update($data);
		$total_videos = Post::count();
		$total_suggested = Post::where('is_trending',1)->count();
		if ($result) {
			$response['success'] = 1;
			$response['total_videos'] = $total_videos;
			$response['total_suggested'] = $total_suggested;
		} else {
			$response['success'] = 0;
			$response['total_videos'] = 0;
			$response['total_suggested'] = 0;
		}
		echo json_encode($response);
	}

	public function showPostList(Request $request)
    {
		$columns = array(
            0=>'post_id',
            1=>'post_image',
            2=>'post_id',
            3=>'post_description',
			4=>'post_hash_tag',
			5=>'video_view_count',
			6=>'is_trending',
			7=>'status',
			8=>'created_at',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');
		$is_trending= $request->input("is_trending");
		$user_id = base64_decode($request->input('user_id'));

		if(empty($request->input('search.value')))
		{

			$query = Post::select('*');
			if($is_trending == 1){
				$query->where('is_trending',$is_trending);
			}
			$PostData = $query->offset($start)
					->limit($limit)
					->orderBy($order,$dir)
					->get();

			$query = Post::select('*');
			if($is_trending == 1){
				$query->where('is_trending',$is_trending);
			}
			$totalData = $totalFiltered = $query->count();

		}
		else {
			$search = $request->input('search.value');
			$query = Post::select('*');
			if($is_trending == 1){
				$query->where('is_trending',$is_trending);
			}
			$PostData = $query->where('post_id','LIKE',"%{$search}%")
							->orWhere('post_description', 'LIKE',"%{$search}%")
							->orWhere('post_hash_tag', 'LIKE',"%{$search}%")
							->offset($start)
							->limit($limit)
							->orderBy($order,$dir)
							->get();

			$query = Post::select('*');
			if($is_trending == 1){
				$query->where('is_trending',$is_trending);
			}
			$totalData = $totalFiltered = $query->where('post_id','LIKE',"%{$search}%")
								->orWhere('post_description', 'LIKE',"%{$search}%")
								->orWhere('post_hash_tag', 'LIKE',"%{$search}%")->count();
		}

		$data = array();
		if(!empty($PostData))
		{
			foreach ($PostData as $rows)
			{
				if(Session::get('admin_id') == 2){
					$disabled = "disabled";
				}else{
					$disabled = "";
				}

				if ($rows->is_trending == 0) {
					$is_trending =  '<span class="badge badge-pill badge-danger">No</span>';
				} elseif ($rows->is_trending == 1) {
					$is_trending =  '<span class="badge badge-pill badge-success">Yes</span>';
				}

				if ($rows->is_trending == 0) {
					$is_trending =  '<span class="badge badge-pill badge-danger">No</span>';
					$trending_btn = '<a class="text-success" id="ChangeTrendingStatus" data-id="'.$rows->post_id.'" data-status="'.$rows->is_trending.'" title="Move to trending" '.$disabled.'><i class="fa fa-reply font-20 pointer p-l-5 p-r-5"></i></a>';
				} elseif ($rows->is_trending == 1) {
					$is_trending =  '<span class="badge badge-pill badge-success">Yes</span>';
					$trending_btn = '<a class="text-danger" id="ChangeTrendingStatus" data-id="'.$rows->post_id.'" data-status="'.$rows->is_trending.'" title="Move to trending" '.$disabled.'><i class="fa fa-mail-forward font-20 pointer p-l-5 p-r-5"></i></a>';
				}

				if(!empty($rows->post_image))
				{
					$post_image = '<img height="50px;" width="50px;" src="'.url(env('DEFAULT_IMAGE_URL').$rows->post_image).'" class="" alt="">';
				}
				else
				{
					$post_image = '<img height="50px;" width="50px;" src="'.asset('assets/dist/img/default.png').'" class="" alt="">';
				}

				if(!empty($rows->post_video))
				{
					$post_video = '<button data-toggle="modal" data-target="#modal-video" data-src="'.url(env('DEFAULT_IMAGE_URL').$rows->post_video).'" class="btn btn-success text-white" id="playvideomdl" title="Play Video"><i class="fa fa-play" style="font-size: 14px;" '.$disabled.'></i></button>';
				}
				else
				{
					$post_video = '';
				}

				$data[]= array(
					$post_video,
					$post_image,
					$rows->user->full_name,
					$rows->post_description,
					$rows->post_hash_tag,
					$rows->video_view_count,
					$is_trending,
					date('Y-m-d h:i:s',strtotime($rows->created_at)),
					$trending_btn,
					'<a class="delete" id="postDelete" data-id="'.$rows->post_id.'" '.$disabled.'><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>
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
}
