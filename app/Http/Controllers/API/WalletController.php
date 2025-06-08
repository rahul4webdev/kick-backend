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
use App\Admin;
use App\Common;
use App\User;
use App\Post;
use App\CoinRate;
use App\CoinPlan;
use App\Gifts;
use App\GlobalSettings;
use App\RewardingAction;
use App\Notification;
use App\RedeemRequest;

class WalletController extends Controller
{
    public function addCoin(Request $request)
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
            'rewarding_action_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $rewarding_action_id = $request->get('rewarding_action_id');

        $settings = GlobalSettings::first();
        $coin = 0;

        if ($rewarding_action_id == 3) {
            $coin = $settings->reward_video_upload;
        }
        $wallet_update = User::where('user_id', $user_id)->increment('my_wallet', $coin);

        if ($wallet_update) {
            return response()->json(['status' => 200, 'message' => "Coin Added Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Add Coin."]);
        }
    }

    public function sendCoin(Request $request)
    {


        $user_id = $request->user()->user_id;
        $full_name = $request->user()->full_name;

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
            'coin' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $to_user_id = $request->get('to_user_id');
        $coin = $request->get('coin');

        $userData =  User::select('my_wallet')->where('user_id', $user_id)->first();
        $wallet = $userData['my_wallet'];

        if ($wallet >= $coin) {
            $count_update = User::where('user_id', $user_id)->where('my_wallet', '>', $coin)->decrement('my_wallet', $coin);
            $wallet_update = User::where('user_id', $to_user_id)->increment('my_wallet', $coin);

            $noti_user_id = $to_user_id;

            $userData =  User::where('user_id', $noti_user_id)->first();
            $platform = $userData['platform'];
            $device_token = $userData['device_token'];
            $message = $full_name . ' sent you ' . $coin . ' Stars';

            $notificationdata = array(
                'sender_user_id' => $user_id,
                'received_user_id' => $noti_user_id,
                'notification_type' => 4,
                'item_id' => $user_id,
                'message' => $message,
            );

            Notification::insert($notificationdata);
            $notification_title = "Shortzz";
            if($userData->is_notification == 1 ){
            Common::send_push($device_token, $notification_title, $message, $platform);
            }
            return response()->json(['status' => 200, 'message' => "Coin Send Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "You have Insufficient Wallet Balance."]);
        }
    }

    public function purchaseCoin(Request $request)
    {


        $user_id = $request->user()->user_id;
        $full_name = $request->user()->full_name;

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
            'coin' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $coin = $request->get('coin');
        $wallet_update = User::where('user_id', $user_id)->increment('my_wallet', $coin);

        return response()->json(['status' => 200, 'message' => "Coin Purchased Successfully."]);
    }

    public function getMyWalletCoin(Request $request)
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

        $data = User::select('my_wallet')->where('user_id', $user_id)->first();

        $data['my_wallet'] = $data->my_wallet ? (int)$data->my_wallet : 0;

        if (!empty($data)) {
            return response()->json(['status' => 200, 'message' => "My Wallet Data Get Successfully.", 'data' => $data]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $data]);
        }
    }

    public function getCoinPlanList(Request $request)
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

        $data = CoinPlan::get();

        if (!empty($data)) {
            return response()->json(['status' => 200, 'message' => "Coin Plan Data Get Successfully.", 'data' => $data]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found.", 'data' => $data]);
        }
    }

    public function redeemRequest(Request $request)
    {


        $user_id = $request->user()->user_id;
        $full_name = $request->user()->full_name;

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
            'amount' => 'required',
            'redeem_request_type' => 'required',
            'account' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }
        $coin = $request->get('coin') ? $request->get('coin') : 0;
        $amount = $request->get('amount');
        $redeem_request_type = $request->get('redeem_request_type');
        $account = $request->get('account');

        $data = array('redeem_request_type' => $redeem_request_type, 'account' => $account, 'amount' => $amount, 'user_id' => $user_id);
        $insert = RedeemRequest::insert($data);

        $update_data = array(
            'my_wallet' => 0,
        );

        $count_update = User::where('user_id', $user_id)->update($update_data);
        if ($insert) {
            return response()->json(['status' => 200, 'message' => "Redeem Request Successfully."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Redeem Request Failed."]);
        }
    }
}
