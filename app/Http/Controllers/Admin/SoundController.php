<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Redirect;
use URL;
use Session;
use Storage;
use App\Admin;
use App\GlobalFunction;
use App\Sound;
use App\SoundCategory;

class SoundController extends Controller
{

	public function viewListSound()
	{
		$total_sound = Sound::where('added_by', 'admin')->count();
		$sound_category_data = SoundCategory::where('is_deleted', 0)->orderBy('sound_category_id', 'DESC')->get();
		return view('admin.sound.sound_list')->with('total_sound', $total_sound)->with('sound_category_data', $sound_category_data);
	}

	public function addUpdateSound(Request $request)
	{
		$sound_id = $request->input('sound_id');
		$sound_category_id = $request->input('sound_category_id');
		$sound_title = $request->input('sound_title');
		$singer = $request->input('singer');
		$duration = $request->input('duration');

		if ($request->hasfile('sound')) {
			$file = $request->file('sound');
			$data['sound'] = GlobalFunction::uploadFilToS3($file);
		}

		if ($request->hasfile('sound_image')) {
			$file = $request->file('sound_image');
			$data['sound_image'] = GlobalFunction::uploadFilToS3($file);
		}

		$data['sound_category_id'] = $sound_category_id;
		$data['sound_title'] = $sound_title;
		$data['singer'] = $singer;
		$data['duration'] = $duration;
		$data['added_by'] = 'admin';

		if (!empty($sound_id)) {
			$result =  Sound::where('sound_id', $sound_id)->update($data);
			$msg = "Update";
			$response['flag'] = 2;
		} else {
			$result =  Sound::insert($data);
			$msg = "Add";
			$response['flag'] = 1;
		}
		$total_sound = Sound::count();
		if ($result) {
			$response['success'] = 1;
			$response['message'] = "Successfully " . $msg . " Sound";
			$response['total_sound'] = $total_sound;
		} else {
			$response['success'] = 0;
			$response['message'] = "Error While " . $msg . " Sound";
			$response['total_sound'] = 0;
		}
		echo json_encode($response);
	}

	public function getSoundByID(Request $request)
	{
		$sound_id = $request->input('sound_id');
		$data = Sound::select('tbl_sound.*', 'st.sound_category_id')->leftjoin('tbl_sound_category as st', 'tbl_sound.sound_category_id', 'st.sound_category_id')->where('tbl_sound.sound_id', $sound_id)->first();

		$response['success'] = 1;
		$response['sound_category_id'] = $data->sound_category_id;
		$response['sound_title'] = $data->sound_title;
		$response['sound'] = url(env('DEFAULT_IMAGE_URL') . $data->sound);
		$response['sound_image'] = url(env('DEFAULT_IMAGE_URL') . $data->sound_image);
		$response['singer'] = $data->singer;
		$response['duration'] = $data->duration;
		echo json_encode($response);
	}

	public function deleteSound(Request $request)
	{

		$sound_id = $request->input('sound_id');
		$sound =  Sound::where('sound_id', $sound_id)->first();
		$sound->is_deleted = 1;
		$result = $sound->save();

		$total_sound = Sound::where('is_deleted', 0)->where('added_by', 'admin')->count();

		if ($result) {
			$response['success'] = 1;
			$response['total_sound'] = $total_sound;
		} else {
			$response['success'] = 0;
			$response['total_sound'] = 0;
		}
		echo json_encode($response);
	}

	public function viewListSoundCategory()
	{
		$total_sound_category = SoundCategory::count();
		return view('admin.sound.sound_category_list')->with('total_sound_category', $total_sound_category);
	}

	public function addUpdateSoundCategory(Request $request)
	{
		$sound_category_id = $request->input('sound_category_id');
		$sound_category_name = $request->input('sound_category_name');

		if ($request->hasfile('sound_category_profile')) {
			$file = $request->file('sound_category_profile');
			$data['sound_category_profile'] = GlobalFunction::uploadFilToS3($file);
		}

		$data['sound_category_name'] = $sound_category_name;

		if (!empty($sound_category_id)) {
			$result =  SoundCategory::where('sound_category_id', $sound_category_id)->update($data);
			$msg = "Update";
			$response['flag'] = 2;
		} else {
			$result =  SoundCategory::insert($data);
			$msg = "Add";
			$response['flag'] = 1;
		}
		$total_sound_category = SoundCategory::count();
		if ($result) {
			$response['success'] = 1;
			$response['message'] = "Successfully " . $msg . " Sound";
			$response['total_sound_category'] = $total_sound_category;
		} else {
			$response['success'] = 0;
			$response['message'] = "Error While " . $msg . " Sound";
			$response['total_sound_category'] = 0;
		}
		echo json_encode($response);
	}

	public function deleteSoundCategory(Request $request)
	{
		$sound_category_id = $request->input('sound_category_id');
		$cat =  SoundCategory::where('sound_category_id', $sound_category_id)->first();

        Sound::where('sound_category_id', $sound_category_id)->update(['is_deleted'=> 1]);

		$cat->is_deleted = 1;
		$result = $cat->save();


		$total_sound_category = SoundCategory::where('is_deleted', 0)->count();

		if ($result) {
			$response['success'] = 1;
			$response['total_sound_category'] = $total_sound_category;
		} else {
			$response['success'] = 0;
			$response['total_sound_category'] = 0;
		}
		echo json_encode($response);
	}

	public function showSoundList(Request $request)
	{

		$columns = array(
			0 => 'sound_id',
			1 => 'sound',
			2 => 'sound_title',
			3 => 'sound_title',
			4 => 'duration',
			5 => 'singer',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		if (empty($request->input('search.value'))) {

			$SoundData = Sound::where('tbl_sound.is_deleted', 0)->where('added_by', 'admin')->select('tbl_sound.*', 'st.sound_category_name')->leftjoin('tbl_sound_category as st', 'tbl_sound.sound_category_id', 'st.sound_category_id')
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = Sound::where('is_deleted', 0)->where('added_by', 'admin')->count();
		} else {
			$search = $request->input('search.value');
			$SoundData = Sound::where('tbl_sound.is_deleted', 0)->where('added_by', 'admin')->select('tbl_sound.*', 'st.sound_category_name')->leftjoin('tbl_sound_category as st', 'tbl_sound.sound_category_id', 'st.sound_category_id')
				->where(function ($query) use ($search) {
					$query->where('tbl_sound.sound_title', 'LIKE', "%{$search}%")
						->orWhere('tbl_sound.sound', 'LIKE', "%{$search}%")
						->orWhere('tbl_sound.duration', 'LIKE', "%{$search}%")
						->orWhere('tbl_sound.singer', 'LIKE', "%{$search}%")
						->orWhere('st.sound_category_name', 'LIKE', "%{$search}%");
				})
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = Sound::where('tbl_sound.is_deleted', 0)->where('added_by', 'admin')->select('tbl_sound.*', 'st.sound_category_name')->leftjoin('tbl_sound_category as st', 'tbl_sound.sound_category_id', 'st.sound_category_id')
				->where(function ($query) use ($search) {
					$query->where('tbl_sound.sound_title', 'LIKE', "%{$search}%")
						->orWhere('tbl_sound.duration', 'LIKE', "%{$search}%")
						->orWhere('tbl_sound.singer', 'LIKE', "%{$search}%")
						->orWhere('st.sound_category_name', 'LIKE', "%{$search}%");
				})
				->count();
		}

		$data = array();
		if (!empty($SoundData)) {
			foreach ($SoundData as $rows) {

				if (!empty($rows->sound_image)) {
					$sound_image = '<img height="60" width="60" src="' . url(env('DEFAULT_IMAGE_URL') . $rows->sound_image) . '">';
				} else {
					$sound_image = '<img height="60px;" width="60px;" src="' . asset('assets/dist/img/default.png') . '">';
				}

				if (!empty($rows->sound)) {
					$sound = '<audio controls>
					<source src="' . url(env('DEFAULT_IMAGE_URL') . $rows->sound) . '" type="audio/mpeg">
					</audio>';
				} else {
					$sound = '';
				}
				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}
				$data[] = array(
					$sound_image,
					$sound,
					$rows->sound_category_name,
					$rows->sound_title,
					$rows->duration,
					$rows->singer,
					'<a class="UpdateSound" data-toggle="modal" data-target="#soundModal" data-id="' . $rows->sound_id . '" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>
					<a class="delete DeleteSound" data-id="' . $rows->sound_id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>'
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

	public function showSoundCategoryList(Request $request)
	{

		$columns = array(
			0 => 'sound_category_id',
			1 => 'sound_category_name',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		if (empty($request->input('search.value'))) {

			$SoundData = SoundCategory::where('is_deleted', 0)
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = SoundCategory::where('is_deleted', 0)->count();
		} else {
			$search = $request->input('search.value');
			$SoundData = SoundCategory::where('is_deleted', 0)
				->where(function ($query) use ($search) {
					$query->where('sound_category_id', 'LIKE', "%{$search}%")
						->orWhere('sound_category_name', 'LIKE', "%{$search}%");
				})
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalData = $totalFiltered = SoundCategory::where('is_deleted', 0)
				->where(function ($query) use ($search) {
					$query->where('sound_category_id', 'LIKE', "%{$search}%")
						->orWhere('sound_category_name', 'LIKE', "%{$search}%");
				})
				->count();
		}

		$data = array();
		if (!empty($SoundData)) {
			foreach ($SoundData as $rows) {

				if (!empty($rows->sound_category_profile)) {
					$sound_category_profile = '<img height="60" width="60" src="' . url(env('DEFAULT_IMAGE_URL') . $rows->sound_category_profile) . '">';
				} else {
					$sound_category_profile = '<img height="60px;" width="60px;" src="' . asset('assets/dist/img/default.png') . '">';
				}
				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}
				$data[] = array(
					$sound_category_profile,
					$rows->sound_category_name,
					'<a class="UpdateSoundCategory" data-toggle="modal" data-target="#soundCategoryModal" data-id="' . $rows->sound_category_id . '" data-name="' . $rows->sound_category_name . '" data-src="' . url(env('DEFAULT_IMAGE_URL') . $rows->sound_category_profile) . '" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>
					<a class="delete DeleteSoundCategory" data-id="' . $rows->sound_category_id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>'
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
