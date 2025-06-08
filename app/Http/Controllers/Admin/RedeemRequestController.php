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
use App\RedeemRequest;
use App\User;

class RedeemRequestController extends Controller
{

	public function viewListRedeemRequest()
	{
		$total_pending_request = RedeemRequest::where('status',0)->count();
        $total_confirm_request = RedeemRequest::where('status',1)->count();
		return view('admin.redeem_request.redeem_request_list')->with('total_pending_request',$total_pending_request)->with('total_confirm_request',$total_confirm_request);
    }

    public function changeRedeemRequestStatus(Request $request){
		$redeem_request_id = $request->input('redeem_request_id');

		$result =  RedeemRequest::where('redeem_request_id',$redeem_request_id)->update(['status'=>1]);
		$total_pending_request = RedeemRequest::where('status',0)->count();
        $total_confirm_request = RedeemRequest::where('status',1)->count();
		if ($result) {
			$response['success'] = 1;
			$response['total_pending_request'] = $total_pending_request;
			$response['total_confirm_request'] = $total_confirm_request;
		} else {
			$response['success'] = 0;
			$response['total_pending_request'] = 0;
			$response['total_confirm_request'] = 0;
		}
		echo json_encode($response);
	}

	public function showRedeemRequestList(Request $request)
    {

		$columns = array( 
            0=>'redeem_request_type',
            1=>'account',
            2=>'amount',
			3=>'user_id',
			4=>'created_at',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');
		$status= $request->input("status");

		if(empty($request->input('search.value')))
		{      
			$query = RedeemRequest::select('tbl_redeem_request.*','u.full_name')->leftjoin('tbl_users as u', 'tbl_redeem_request.user_id', 'u.user_id');
			$query->where('tbl_redeem_request.status',$status);
			$RedeemRequestData = $query->offset($start)
					->limit($limit)
					->orderBy($order,$dir)
					->get();
			$totalData = $totalFiltered = RedeemRequest::where('status',$status)->count();

		}
		else {
			$search = $request->input('search.value'); 
			$query =  RedeemRequest::select('tbl_redeem_request.*','u.full_name')->leftjoin('tbl_users as u', 'tbl_redeem_request.user_id', 'u.user_id')->where('tbl_redeem_request.redeem_request_id','LIKE',"%{$search}%")
						->orWhere('tbl_redeem_request.redeem_request_type', 'LIKE',"%{$search}%")
						->orWhere('tbl_redeem_request.account', 'LIKE',"%{$search}%")
						->orWhere('tbl_redeem_request.amount', 'LIKE',"%{$search}%")
						->orWhere('u.full_name', 'LIKE',"%{$search}%");
						$query->where('tbl_redeem_request.status',$status);
			$RedeemRequestData = $query->offset($start)
						->limit($limit)
						->orderBy($order,$dir)
						->get();

			$query =  RedeemRequest::select('tbl_redeem_request.*','u.full_name')->leftjoin('tbl_users as u', 'tbl_redeem_request.user_id', 'u.user_id')->where('tbl_redeem_request.redeem_request_id','LIKE',"%{$search}%")
						->orWhere('tbl_redeem_request.redeem_request_type', 'LIKE',"%{$search}%")
						->orWhere('tbl_redeem_request.account', 'LIKE',"%{$search}%")
						->orWhere('tbl_redeem_request.amount', 'LIKE',"%{$search}%")
						->orWhere('u.full_name', 'LIKE',"%{$search}%");
						$query->where('tbl_redeem_request.status',$status);
			$totalData	= $totalFiltered = $query->count();
		}

		$data = array();
		if(!empty($RedeemRequestData))
		{
			foreach ($RedeemRequestData as $rows)
			{
				if(Session::get('admin_id') == 2){ 
					$disabled = "disabled";
				}else{
					$disabled = "";
				}
				
				if ($rows->status == 0) {
					$status =  '<span class="badge badge-pill badge-warning">Pending</span>';
				} elseif ($rows->status == 1) {
					$status =  '<span class="badge badge-pill badge-success">Confirmed</span>';
				}

				if ($rows->status == 0) {
					$btn =  '<a data-id="'.$rows->redeem_request_id.'" class="settings changeRedeemRequestStatus" title="Confirm Redeem Request" data-toggle="tooltip" data-original-title="Confirm Redeem Request" '.$disabled.'><i class="fa fa-share text-success font-20 pointer p-l-5 p-r-5"></i></a>';
				} elseif ($rows->status == 1) {
					$btn =  '';
				}

				if ($rows->status == 0) {
					$data[]= array(
						$rows->redeem_request_type,
						$rows->account,
						$rows->amount,
						$rows->full_name,
						date('Y-m-d',strtotime($rows->created_at)),
						$status,
						$btn
					); 
				}else{
					$data[]= array(
						$rows->redeem_request_type,
						$rows->account,
						$rows->amount,
						$rows->full_name,
						date('Y-m-d',strtotime($rows->created_at)),
						$status,
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
