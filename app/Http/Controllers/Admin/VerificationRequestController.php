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
use App\Notification;
use App\Common;
use App\VerificationRequest;

class VerificationRequestController extends Controller
{

	public function viewListVerificationRequest()
	{
		$total_verification_request = VerificationRequest::count();
		return view('admin.verification_request.verification_request_list')->with('total_verification_request',$total_verification_request);
    }

	public function verifyRequest(Request $request){

		$verification_request_id = $request->input('verification_request_id');
		$message_text = 'Your profile is verified';

		$verification_request = VerificationRequest::where('verification_request_id',$verification_request_id)->first();
        $user_id = $verification_request['user_id'];

		$noti_data = User::where('user_id',$user_id)->first();
        $platform = $noti_data['platform'];
        $device_token = $noti_data['device_token'];

        $notificationdata = array(
                'sender_user_id'=>0,
                'received_user_id'=>$user_id,
                'notification_type'=>5,
                'message'=>$message_text,
            );
		Notification::insert($notificationdata);
		$notification_title = "Shortzz";
        if($noti_data->is_notification == 1){
        Common::send_push($device_token,$notification_title,$message_text,$platform);
        }

		$data = array('status'=>1);
		VerificationRequest::where('verification_request_id',$verification_request_id)->update($data);
		$data1 = array('is_verify'=>1);
		$result = User::where('user_id',$user_id)->update($data1);

		$total_verification_request = VerificationRequest::count();

		if ($result) {
			$response['success'] = 1;
			$response['total_verification_request'] = $total_verification_request;
		} else {
			$response['success'] = 0;
			$response['total_verification_request'] = 0;
		}
		echo json_encode($response);

	}

	public function deleteVerificationRequest(Request $request){

		$verification_request_id = $request->input('verification_request_id');
		$message_text = $request->input('message_text');

		$verification_request = VerificationRequest::where('verification_request_id',$verification_request_id)->first();
        $user_id = $verification_request['user_id'];

		$noti_data = User::where('user_id',$user_id)->first();
        $platform = $noti_data['platform'];
        $device_token = $noti_data['device_token'];

        $notificationdata = array(
                'sender_user_id'=>0,
                'received_user_id'=>$user_id,
                'notification_type'=>5,
                'message'=>$message_text,
            );
		Notification::insert($notificationdata);
		$notification_title = "Shortzz";
        if($noti_data->is_notification == 1){
		Common::send_push($device_token,$notification_title,$message_text,$platform);
        }
		$result =  VerificationRequest::where('verification_request_id',$verification_request_id)->delete();

		$data1 = array('is_verify'=>0);
		$result = User::where('user_id',$user_id)->update($data1);

		$total_verification_request = VerificationRequest::count();

		if ($result) {
			$response['success'] = 1;
			$response['total_verification_request'] = $total_verification_request;
		} else {
			$response['success'] = 0;
			$response['total_verification_request'] = 0;
		}
		echo json_encode($response);

	}

    public function showVerificationRequestList(Request $request)
    {

		$columns = array(
            0=>'photo_id_image',
            1=>'photo_with_id_image',
			2=>'user_id',
			3=>'id_number',
			4=>'name',
			5=>'address',
			6=>'created_at',
		);

		$limit = $request->input('length');
		$start = $request->input('start');
		$order = $columns[$request->input('order.0.column')];
		$dir = $request->input('order.0.dir');

		if(empty($request->input('search.value')))
		{

			$VerificationRequestData = VerificationRequest::select('tbl_verification_request.*','u.full_name')->leftjoin('tbl_users as u', 'tbl_verification_request.user_id', 'u.user_id')
					->offset($start)
					->limit($limit)
					->orderBy($order,$dir)
					->get();

			$totalData = $totalFiltered = VerificationRequest::count();

		}
		else {
			$search = $request->input('search.value');
			$VerificationRequestData = VerificationRequest::select('tbl_verification_request.*','u.full_name')->leftjoin('tbl_users as u', 'tbl_verification_request.user_id', 'u.user_id')->where('tbl_verification_request.verification_request_id','LIKE',"%{$search}%")
							->orWhere('tbl_verification_request.id_number', 'LIKE',"%{$search}%")
							->orWhere('tbl_verification_request.name', 'LIKE',"%{$search}%")
							->orWhere('tbl_verification_request.address', 'LIKE',"%{$search}%")
							->orWhere('u.full_name', 'LIKE',"%{$search}%")
							->offset($start)
							->limit($limit)
							->orderBy($order,$dir)
							->get();

			$totalData = $totalFiltered = VerificationRequest::select('tbl_verification_request.*','u.full_name')->leftjoin('tbl_users as st', 'tbl_verification_request.user_id', 'st.user_id')->where('tbl_verification_request.verification_request_id','LIKE',"%{$search}%")
						->orWhere('tbl_verification_request.id_number', 'LIKE',"%{$search}%")
						->orWhere('tbl_verification_request.name', 'LIKE',"%{$search}%")
						->orWhere('tbl_verification_request.address', 'LIKE',"%{$search}%")
						->orWhere('u.full_name', 'LIKE',"%{$search}%")
						->count();
		}

		$data = array();
		if(!empty($VerificationRequestData))
		{
			foreach ($VerificationRequestData as $rows)
			{
				if(Session::get('admin_id') == 2){
					$disabled = "disabled";
				}else{
					$disabled = "";
				}
				if ($rows->status == 0) {
					$status =  '<span class="badge badge-pill badge-warning">Pending</span>';
					$html = '<a class="text-success VerifyRequest" data-id="'.$rows->verification_request_id.'" title="Verify Request" '.$disabled.'><i class="fa fa-reply font-20 pointer p-l-5 p-r-5"></i></a>';
				} elseif ($rows->status == 1) {
					$status =  '<span class="badge badge-pill badge-success">Completed</span>';
					$html = '';
				}

                if(!empty($rows->photo_id_image))
                {
                    $photo_id_image = '<img height="60" width="60" src="'.url(env('DEFAULT_IMAGE_URL').$rows->photo_id_image).'">';
                }
                else
                {
                    $photo_id_image = '<img height="60px;" width="60px;" src="'.asset('assets/dist/img/default.png').'">';
                }


                if(!empty($rows->photo_with_id_image))
                {
                    $photo_with_id_image = '<img height="60" width="60" src="'.url(env('DEFAULT_IMAGE_URL').$rows->photo_with_id_image).'">';
                }
                else
                {
                    $photo_with_id_image = '<img height="60px;" width="60px;" src="'.asset('assets/dist/img/default.png').'">';
                }

				$data[]= array(
					$photo_id_image,
					$photo_with_id_image,
					$rows->full_name,
					$rows->id_number,
					$rows->name,
					$rows->address,
					date('Y-m-d h:i:s',strtotime($rows->created_at)),
					$status,
					$html.'<a class="delete DeleteVerificationRequest" data-id="'.$rows->verification_request_id.'" title="Delete Verification Request" '.$disabled.'><i class="fas fa-trash text-danger font-20 pointer p-l-5 p-r-5"></i></a>'
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
