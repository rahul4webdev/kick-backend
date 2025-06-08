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
use App\Report;
use App\User;
use App\Post;
use App\Like;
use App\Comments;
use App\Bookmark;

class ReportController extends Controller
{

	public function viewListReport()
	{
		$total_report = Report::count();
		$total_report_video = Report::where('report_type', 2)->count();
		$total_report_user = Report::where('report_type', 1)->count();
		return view('admin.report.report_list')->with('total_report', $total_report)->with('total_report_video', $total_report_video)->with('total_report_user', $total_report_user);
	}

	public function confirmReport(Request $request)
	{

		$report_id = $request->input('report_id');

		$report = Report::where('report_id', $report_id)->first();
		$report_type = $report['report_type'];

		$post_id = $report['post_id'];
		$user_id = $report['user_id'];

		if ($report_type == 1) {
			$post_list = Post::where('user_id', $user_id)->get();

			foreach ($post_list as $value) {
				Post::where('post_id', $value->post_id)->delete();
				Like::where('post_id', $value->post_id)->delete();
				Comments::where('post_id', $value->post_id)->delete();
				Bookmark::where('post_id', $value->post_id)->delete();
			}
		} else {
			Post::where('post_id', $post_id)->delete();
			Like::where('post_id', $post_id)->delete();
			Comments::where('post_id', $post_id)->delete();
			Bookmark::where('post_id', $post_id)->delete();
		}


		$result = Report::where('report_id', $report_id)->delete();

		$total_report = Report::count();
		$total_report_video = Report::where('report_type', 2)->count();
		$total_report_user = Report::where('report_type', 1)->count();

		if ($result) {
			$response['success'] = 1;
			$response['total_report'] = $total_report;
			$response['total_report_video'] = $total_report_video;
			$response['total_report_user'] = $total_report_user;
		} else {
			$response['success'] = 0;
			$response['total_report'] = 0;
			$response['total_report_video'] = 0;
			$response['total_report_user'] = 0;
		}
		echo json_encode($response);
	}

	public function deleteReport(Request $request)
	{

		$report_id = $request->input('report_id');

		$result =  Report::where('report_id', $report_id)->delete();
		$total_report = Report::count();
		$total_report_video = Report::where('report_type', 2)->count();
		$total_report_user = Report::where('report_type', 1)->count();

		if ($result) {
			$response['success'] = 1;
			$response['total_report'] = $total_report;
			$response['total_report_video'] = $total_report_video;
			$response['total_report_user'] = $total_report_user;
		} else {
			$response['success'] = 0;
			$response['total_report'] = 0;
			$response['total_report_video'] = 0;
			$response['total_report_user'] = 0;
		}
		echo json_encode($response);
	}

	public function showReportList(Request $request)
	{

		$columns = array(
			0 => 'report_id',
			1 => 'user_id',
			2 => 'post_id',
			3 => 'reason',
			4 => 'description',
			5 => 'contact_info',
			6 => 'created_at',

		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');
		$report_type = $request->input("report_type");

		if (empty($request->input('search.value'))) {
			$query = Report::select('*');
			$query->where('report_type', $report_type);
			$ReportData = $query->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();
			$totalData = $totalFiltered = Report::where('report_type', $report_type)->count();
		} else {
			$search = $request->input('search.value');
			$query =  Report::select('*')->where('report_id', 'LIKE', "%{$search}%")
				->orWhere('report_type', 'LIKE', "%{$search}%")
				->orWhere('user_id', 'LIKE', "%{$search}%")
				->orWhere('post_id', 'LIKE', "%{$search}%")
				->orWhere('reason', 'LIKE', "%{$search}%")
				->orWhere('description', 'LIKE', "%{$search}%")
				->orWhere('contact_info', 'LIKE', "%{$search}%");
			$query->where('report_type', $report_type);
			$ReportData = $query->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$query =  Report::select('*')->where('report_id', 'LIKE', "%{$search}%")
				->orWhere('report_type', 'LIKE', "%{$search}%")
				->orWhere('user_id', 'LIKE', "%{$search}%")
				->orWhere('post_id', 'LIKE', "%{$search}%")
				->orWhere('reason', 'LIKE', "%{$search}%")
				->orWhere('description', 'LIKE', "%{$search}%")
				->orWhere('contact_info', 'LIKE', "%{$search}%");
			$query->where('report_type', $report_type);
			$totalData	= $totalFiltered = $query->count();
		}

		$data = array();
		if (!empty($ReportData)) {
			$post_video = '';
			foreach ($ReportData as $rows) {

				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}

				if ($report_type == 1) {
					$userdata =  User::where('user_id', $rows->user_id)->first();
				} else {
					$post_data =  Post::where('post_id', $rows->post_id)->first();
					$userdata =  User::where('user_id', $post_data['user_id'])->first();
					if ($post_data && $post_data['post_video'] != "") {
						$post_video = '<button data-toggle="modal" data-target="#modal-video" data-src="' . url(env('DEFAULT_IMAGE_URL') . $post_data['post_video']) . '" class="btn btn-success text-white" id="playvideomdl" title="Play Video"><i class="fa fa-play" style="font-size: 14px;"></i></button>';
					}
				}
				$username = $userdata['full_name'];

				if ($rows->status == 0) {
					$status =  '<span class="badge badge-pill badge-warning">Pending</span>';
					$html = '<a class="text-success confirmReport" data-id="' . $rows->report_id . '" title="Verify Request" ' . $disabled . '><i class="fa fa-reply font-20 pointer p-l-5 p-r-5"></i></a>';
				} elseif ($rows->status == 1) {
					$status =  '<span class="badge badge-pill badge-success">Completed</span>';
					$html = '';
				}

				if ($userdata && $userdata->user_profile != "") {
					$profile = "<img style='height:50px;' style='width:50px;' src = " . url(env('DEFAULT_IMAGE_URL') . $userdata['user_profile']) . ">";
				} else {
					$profile = '<img height="50px;" width="50px;" src="' . asset('assets/dist/img/default.png') . '" class="" alt="">';
				}

				if ($report_type == 1) {
					$report_type = 'Report User';
					$data[] = array(
						$report_type,
						$username,
						$profile,
						$rows->reason,
						$rows->description,
						$rows->contact_info,
						$rows->created_date,
						$status,
						$html . '<a class="delete DeleteReport" data-id="' . $rows->report_id . '" title="Delete Verification Request" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>'
					);
				} else {
					$report_type = 'Report Video';
					$data[] = array(
						$report_type,
						$username,
						$post_video,
						$rows->reason,
						$rows->description,
						$rows->contact_info,
						$rows->created_date,
						$status,
						$html . '<a class="delete DeleteReport" data-id="' . $rows->report_id . '" title="Delete Verification Request"><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>'
					);
				}
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
