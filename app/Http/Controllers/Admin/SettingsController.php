<?php

namespace App\Http\Controllers\Admin;

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
use App\GlobalSettings;

class SettingsController extends Controller
{
    function changeCompressStatus(Request $request){
        $settings = GlobalSettings::first();
        $settings->is_compress = $request->status;
        $settings->save();
        return response()->json(['status' => true, 'message' => 'settings updated successfully !']);
    }
    function changeContentModerationStatus(Request $request){
        $settings = GlobalSettings::first();
        $settings->is_content_moderation = $request->status;
        $settings->save();
        return response()->json(['status' => true, 'message' => 'settings updated successfully !']);
    }
	function fetchSettingsData(Request $request)
	{
		$headers = $request->headers->all();
		$verify_request_base = Admin::verify_request_base($headers);

		if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
			return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
			exit();
		}

		$gifts = Gifts::all();
		$agora_app_id = env('AGORA_APP_ID');
		$agora_app_cert = env('AGORA_APP_CERT');
		$settings = GlobalSettings::first();
		$settings->agora_app_cert = $agora_app_cert;
		$settings->agora_app_id = $agora_app_id;
		$settings->gifts = $gifts;
		return response()->json(['status' => 200, 'message' => "Settings Data Get Successfully.", 'data' => $settings]);
	}
	function viewSettings()
	{
		$settings = GlobalSettings::first();
		return view('settings', ['data' => $settings]);
	}

	function updateGlobalSettings(Request $request)
	{
		$settings = GlobalSettings::first();
		$settings->coin_value = $request->coin_value;
		$settings->currency = $request->currency;
		$settings->help_mail = $request->help_mail;
		$settings->min_fans_verification = $request->min_fans_verification;
		$settings->min_redeem_coins = $request->min_redeem_coins;
		$settings->reward_video_upload = $request->reward_video_upload;
		$settings->admob_banner = $request->admob_banner;
		$settings->admob_banner_ios = $request->admob_banner_ios;
		$settings->admob_int = $request->admob_int;
		$settings->admob_int_ios = $request->admob_int_ios;
		$settings->live_min_viewers = $request->live_min_viewers;
		$settings->live_timeout = $request->live_timeout;
		$settings->max_upload_daily = $request->max_upload_daily;
		$settings->min_fans_for_live = $request->min_fans_for_live;
        
		$settings->sight_engine_api_secret = $request->sight_engine_api_secret;
		$settings->sight_engine_api_user = $request->sight_engine_api_user;
		$settings->save();
		return response()->json(['status' => true, 'message' => 'settings updated successfully !']);
	}
}
