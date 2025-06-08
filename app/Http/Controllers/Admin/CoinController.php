<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Redirect;
use URL;
use Hash;
use Session;
use DB;
use File;
use App\Admin;
use App\CoinRate;
use App\CoinPlan;
use App\Gifts;
use App\GlobalFunction;

class CoinController extends Controller
{

	function viewListGifts()
	{
		$totalGifts = Gifts::count();
		return view('gifts')->with('total_gifts', $totalGifts);
	}

	public function viewListCoinPlan()
	{
		$total_coin_plan = CoinPlan::count();
		return view('admin.coin.coin_plan_list')->with('total_coin_plan', $total_coin_plan);
	}

	function addUpdateGift(Request $request)
	{
		$gift_id = $request->input('gift_id');
		$coin_price = $request->input('coin_price');

		if ($request->hasfile('gift_image')) {
			$file = $request->file('gift_image');
			$data['image'] = GlobalFunction::uploadFilToS3($file);
		}

		$data['coin_price'] = $coin_price;

		if (!empty($gift_id)) {
			$result =  Gifts::where('id', $gift_id)->update($data);
			$msg = "Update";
			$response['flag'] = 2;
		} else {
			$result =  Gifts::insert($data);
			$msg = "Add";
			$response['flag'] = 1;
		}
		$total_gifts = Gifts::count();
		if ($result) {
			$response['success'] = 1;
			$response['message'] = "Successfully " . $msg . " Sound";
			$response['total_gifts'] = $total_gifts;
		} else {
			$response['success'] = 0;
			$response['message'] = "Error While " . $msg . " Sound";
			$response['total_gifts'] = 0;
		}
		echo json_encode($response);
	}


	public function addUpdateCoinPlan(Request $request)
	{
		$coin_plan_id = $request->input('coin_plan_id');
		$coin_plan_name = $request->input('coin_plan_name');
		$coin_plan_description = $request->input('coin_plan_description');
		$coin_plan_price = $request->input('coin_plan_price');
		$coin_amount = $request->input('coin_amount');
		$playstore_product_id = $request->input('playstore_product_id');
		$appstore_product_id = $request->input('appstore_product_id');

		$data['coin_plan_name'] = $coin_plan_name;
		$data['coin_plan_description'] = $coin_plan_description;
		$data['coin_plan_price'] = $coin_plan_price;
		$data['coin_amount'] = $coin_amount;
		$data['playstore_product_id'] = $playstore_product_id;
		$data['appstore_product_id'] = $appstore_product_id;

		if (!empty($coin_plan_id)) {
			$result =  CoinPlan::where('coin_plan_id', $coin_plan_id)->update($data);
			$msg = "Update";
			$response['flag'] = 2;
		} else {
			$result =  CoinPlan::insert($data);
			$msg = "Add";
			$response['flag'] = 1;
		}
		$total_coin_plan = CoinPlan::count();
		if ($result) {
			$response['success'] = 1;
			$response['message'] = "Successfully " . $msg . " Coin Plan";
			$response['total_coin_plan'] = $total_coin_plan;
		} else {
			$response['success'] = 0;
			$response['message'] = "Error While " . $msg . " Coin Plan";
			$response['total_coin_plan'] = 0;
		}
		echo json_encode($response);
	}

	function deleteGift(Request $request)
	{
		$item_id = $request->input('item_id');
		$result =  Gifts::where('id', $item_id)->delete();
		$total_gifts = Gifts::count();
		if ($result) {
			$response['success'] = 1;
			$response['total_gifts'] = $total_gifts;
		} else {
			$response['success'] = 0;
			$response['total_gifts'] = 0;
		}
		echo json_encode($response);
	}

	public function deleteCoinPlan(Request $request)
	{

		$coin_plan_id = $request->input('coin_plan_id');
		$result =  CoinPlan::where('coin_plan_id', $coin_plan_id)->delete();
		$total_coin_plan = CoinPlan::count();
		if ($result) {
			$response['success'] = 1;
			$response['total_coin_plan'] = $total_coin_plan;
		} else {
			$response['success'] = 0;
			$response['total_coin_plan'] = 0;
		}
		echo json_encode($response);
	}

	public function showCoinPlanList(Request $request)
	{

		$columns = array(
			0 => 'coin_plan_name',
			1 => 'coin_plan_description',
			2 => 'coin_plan_price',
			3 => 'coin_amount',
			4 => 'playstore_product_id',
			5 => 'appstore_product_id',
			6 => 'created_at'
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		if (empty($request->input('search.value'))) {
			$CoinPlanData = CoinPlan::offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();
			$totalData = $totalFiltered = CoinPlan::count();
		} else {
			$search = $request->input('search.value');
			$CoinPlanData =  CoinPlan::where('coin_plan_id', 'LIKE', "%{$search}%")
				->orWhere('coin_plan_name', 'LIKE', "%{$search}%")
				->orWhere('coin_plan_description', 'LIKE', "%{$search}%")
				->orWhere('coin_plan_price', 'LIKE', "%{$search}%")
				->orWhere('coin_amount', 'LIKE', "%{$search}%")
				->orWhere('playstore_product_id', 'LIKE', "%{$search}%")
				->orWhere('appstore_product_id', 'LIKE', "%{$search}%")
				->orWhere('created_at', 'LIKE', "%{$search}%")
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalFiltered = CoinPlan::where('coin_plan_id', 'LIKE', "%{$search}%")
				->orWhere('coin_plan_name', 'LIKE', "%{$search}%")
				->orWhere('coin_plan_description', 'LIKE', "%{$search}%")
				->orWhere('coin_plan_price', 'LIKE', "%{$search}%")
				->orWhere('coin_amount', 'LIKE', "%{$search}%")
				->orWhere('playstore_product_id', 'LIKE', "%{$search}%")
				->orWhere('appstore_product_id', 'LIKE', "%{$search}%")
				->orWhere('created_at', 'LIKE', "%{$search}%")
				->count();
		}

		$data = array();
		if (!empty($CoinPlanData)) {
			foreach ($CoinPlanData as $rows) {

				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}

				$data[] = array(
					$rows->coin_plan_name,
					$rows->coin_plan_description,
					$rows->coin_plan_price,
					$rows->coin_amount,
					$rows->playstore_product_id,
					$rows->appstore_product_id,
					date('Y-m-d', strtotime($rows->created_at)),
					'<a data-toggle="modal" data-target="#coinPlanModal" data-id="' . $rows->coin_plan_id . '"
					data-name="' . $rows->coin_plan_name . '" data-description="' . $rows->coin_plan_description . '" data-price="' . $rows->coin_plan_price . '" data-amount="' . $rows->coin_amount . '" data-playstore_product_id="' . $rows->playstore_product_id . '" data-appstore_product_id="' . $rows->appstore_product_id . '" class="settings UpdateCoinPlan" title="Edit CoinPlan" data-toggle="tooltip" data-original-title="Edit CoinPlan" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>
					<a class="delete" id="deleteCoinPlan" data-id="' . $rows->coin_plan_id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>',
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
	public function showGiftsList(Request $request)
	{

		$columns = array(
			0 => 'id',
			1 => 'coin_price',
			2 => 'image',
			3 => 'created_at'
		);

		$totalData = Gifts::count();

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		$totalFiltered = $totalData;
		if (empty($request->input('search.value'))) {
			$Items = Gifts::offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();
			$totalData = $totalFiltered = Gifts::count();
		} else {
			$search = $request->input('search.value');
			$Items =  Gifts::where('coin_price', 'LIKE', "%{$search}%")
				->offset($start)
				->limit($limit)
				->orderBy($order, $dir)
				->get();

			$totalFiltered = Gifts::where('coin_price', 'LIKE', "%{$search}%")
				->count();
		}

		$data = array();
		if (!empty($Items)) {
			foreach ($Items as $rows) {

				if (Session::get('admin_id') == 2) {
					$disabled = "disabled";
				} else {
					$disabled = "";
				}

				$imageUrl = url(env('DEFAULT_IMAGE_URL') . $rows->image);

				$image = '<img src="' . $imageUrl . '" width="50" height="50">';

				$edit = '<a data-toggle="modal" data-target="#giftsModal" data-id="' . $rows->id . '"
					data-price="' . $rows->coin_price . '" data-image="' . $rows->image . '" class="settings updateGift" title="Edit Gift" data-toggle="tooltip" data-original-title="Edit Gift" ' . $disabled . '><i class="i-cl-3 fas fa-edit col-blue font-20 pointer p-l-5 p-r-5"></i></a>';

				$delete = '<a class="delete" id="deleteGift" data-id="' . $rows->id . '" ' . $disabled . '><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>';

				$actions = $edit . $delete;


				$data[] = array(
					$image,
					$rows->coin_price,
					$actions
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
