<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Redirect;
use URL;
use Storage;
use Session;
use App\Admin;
use App\GlobalFunction;
use App\HashTags;
use App\Post;

class HashTagsController extends Controller
{

	public function viewListHashTags()
	{
		$total_hashtags = HashTags::where('move_explore', 0)->count();
		$total_explore_tag = HashTags::where('move_explore', 1)->count();
		return view('admin.hashtags.hashtags_list')->with('total_hashtags', $total_hashtags)->with('total_explore_tag', $total_explore_tag);
	}

	public function ExploreHashtagImage(Request $request)
	{
		$hash_tag_id = $request->input('hash_tag_id');

		$hashtagimgname = $imageFileName = "";
		if ($request->hasfile('hash_tag_profile')) {
			$file = $request->file('hash_tag_profile');
			$hashtagimgname = GlobalFunction::uploadFilToS3($file);
		} else {
			$hashtagimgname = $request->input('hdn_hash_tag_image');
		}
		$data['hash_tag_profile'] = $hashtagimgname;
		$data['move_explore'] = 1;
		if (!empty($hash_tag_id)) {
			$result =  HashTags::where('hash_tag_id', $hash_tag_id)->update($data);
			$msg = "Update";
			$response['flag'] = 2;
		} else {
			$result =  HashTags::insert($data);
			$msg = "Add";
			$response['flag'] = 1;
		}
		$total_hashtags = HashTags::where('move_explore', 0)->count();
		$total_explore_tag = HashTags::where('move_explore', 1)->count();
		if ($result) {
			$response['success'] = 1;
			$response['message'] = "Successfully " . $msg . " HashTags";
			$response['total_hashtags'] = $total_hashtags;
			$response['total_explore_tag'] = $total_explore_tag;
		} else {
			$response['success'] = 0;
			$response['message'] = "Error While " . $msg . " HashTags";
			$response['total_hashtags'] = 0;
			$response['total_explore_tag'] = 0;
		}
		echo json_encode($response);
	}

	public function RemoveExploreHashTags(Request $request)
	{

		$hash_tag_id = $request->input('hash_tag_id');
		$data['move_explore'] = 0;
		$result =  HashTags::where('hash_tag_id', $hash_tag_id)->update($data);
		$total_hashtags = HashTags::where('move_explore', 0)->count();
		$total_explore_tag = HashTags::where('move_explore', 1)->count();
		if ($result) {
			$response['success'] = 1;
			$response['total_hashtags'] = $total_hashtags;
			$response['total_explore_tag'] = $total_explore_tag;
		} else {
			$response['success'] = 0;
			$response['total_hashtags'] = 0;
			$response['total_explore_tag'] = 0;
		}
		echo json_encode($response);
	}

	public function showHashTagsList(Request $request)
	{

		$columns = array(
			0 => 'hash_tag_name',
			1 => 'hash_tag_profile',
			2 => 'hash_tag_id',

		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');
		$move_explore = $request->input("move_explore");

		if (empty($request->input('search.value'))) {
			$query = HashTags::select('*');
			$query->where('move_explore', $move_explore);
			$HashTagsData = $query->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();
			$totalData = $totalFiltered = HashTags::where('move_explore', $move_explore)->count();
		} else {
			$search = $request->input('search.value');
			$query =  HashTags::select('*')->where('hash_tag_id', 'LIKE', "%{$search}%")
				->orWhere('hash_tag_name', 'LIKE', "%{$search}%");
			$query->where('move_explore', $move_explore);
			$HashTagsData = $query->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$query =  HashTags::select('*')->where('hash_tag_id', 'LIKE', "%{$search}%")
				->orWhere('hash_tag_name', 'LIKE', "%{$search}%");
			$query->where('move_explore', $move_explore);
			$totalData	= $totalFiltered = $query->count();
		}

		$data = array();
		if (!empty($HashTagsData)) {
			foreach ($HashTagsData as $rows) {

				$hash_tag_videos_count = Post::where('post_hash_tag', $rows->hash_tag_name)->count();

				if ($rows->move_explore == 0) {
					$move_explore =  '<span class="badge badge-pill badge-warning">Pending</span>';
				} elseif ($rows->move_explore == 1) {
					$move_explore =  '<span class="badge badge-pill badge-success">Completed</span>';
				}

				if ($rows->hash_tag_profile != "") {
					$profile = url(env('DEFAULT_IMAGE_URL') . $rows->hash_tag_profile);
				} else {
					$profile = '';
				}


				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}

				if ($rows->move_explore == 0) {
					$btn =  '<a data-toggle="modal" data-target="#hashtagsModal" data-id="' . $rows->hash_tag_id . '"
					  data-src="' . $rows->hash_tag_profile . '" class="settings UpdateHashTags" title="Move to Explore" data-toggle="tooltip" data-original-title="Edit Image" ' . $disabled . '><i class="fa fa-reply text-success font-20 pointer p-l-5 p-r-5"></i></a>
					<a data-toggle="modal" data-target="#hashtagsModal" data-id="' . $rows->hash_tag_id . '"
					  data-src="' . $rows->hash_tag_profile . '" class="settings UpdateHashTags" title="Edit Image" data-toggle="tooltip" data-original-title="Edit Image" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>';
				} elseif ($rows->move_explore == 1) {
					$btn =  '<a class="text-danger RemoveExploreHashTags" data-id="' . $rows->hash_tag_id . '" title="Remove From explore" ' . $disabled . '><i class="fa fa-mail-forward font-20 pointer p-l-5 p-r-5"></i></a>
					<a data-toggle="modal" data-target="#hashtagsModal" data-id="' . $rows->hash_tag_id . '"
					  data-src="' . $rows->hash_tag_profile . '" class="settings UpdateHashTags" title="Edit Image" data-toggle="tooltip" data-original-title="Edit Image" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>';
				}

				if ($rows->hash_tag_profile != "") {
					$hash_tag_profile = "<img style='height:50px;' style='width:50px;' src = " . url(env('DEFAULT_IMAGE_URL') . $rows->hash_tag_profile) . ">";
				} else {
					$hash_tag_profile = '';
				}

				$data[] = array(
					$rows->hash_tag_name,
					$hash_tag_profile,
					$hash_tag_videos_count,
					$move_explore,
					$btn
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
