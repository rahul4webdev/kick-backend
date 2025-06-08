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
use Storage;
use Carbon\Carbon;
use App\Admin;
use App\User;
use App\Post;
use App\Sound;
use App\SoundCategory;
use App\HashTags;
use App\VerificationRequest;
use App\Report;
use App\CoinRate;
use App\GlobalFunction;
use App\GlobalSettings;
use App\RedeemRequest;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{

	public function showLogin()
	{

		if (Session::get('email') && Session::get('is_user') == 1) {
			return Redirect::route('dashboard');
		} else {
			return view('admin.login');
		}
	}

	public function dologin(Request $request)
	{
		$username = $request->input('username');
		$password = $request->input('password');
		$checkLogin = Admin::where('admin_name', $username)->first();

		if (!empty($checkLogin)) {
			if ($checkLogin->admin_password == $password || Hash::check($password, $checkLogin->admin_password)) {
				Session::put('name', $checkLogin->admin_name);
				Session::put('email', $checkLogin->admin_email);
				Session::put('admin_id', $checkLogin->admin_id);
				Session::put('profile_image', url(env('DEFAULT_IMAGE_URL') . $checkLogin->admin_profile));
				Session::put('is_logged', 1);
				Session::put('is_admin', 1);

				return Redirect::route('dashboard');
			} else {
				Session::flash('invalid', 'Invalid username or password combination. Please try again.');
				return back();
			}
		} else {
			Session::flash('invalid', 'Invalid username or password combination. Please try again.');
			return back();
		}
	}

	public function showDashboard()
	{
		if (Session::get('name') && Session::get('is_logged') == 1) {

			$settings = GlobalSettings::first();

			$totalUser = User::count();
			$totalTodayUser = User::whereDate('created_at', Carbon::today())->count();
			$totalVerifyUser = User::where('is_verify', 1)->count();

			$totalPost = Post::count();

			$totalSound = Sound::where('added_by', 'admin')->count();
			$totalSoundCategory = SoundCategory::count();

			$totalHashTags = HashTags::where('move_explore', 1)->count();
			$totalVerificationRequest = VerificationRequest::count();
			$totalReport = Report::count();

			$CoinRate = $settings->coin_value;
			$MyWallet = User::where('status', 1)->sum('my_wallet');

			$totalRedeemRequest = RedeemRequest::count();

			return view('admin.dashboard')->with('totalUser', $totalUser)->with('totalTodayUser', $totalTodayUser)->with('totalVerifyUser', $totalVerifyUser)->with('totalPost', $totalPost)->with('totalSound', $totalSound)->with('totalSoundCategory', $totalSoundCategory)->with('totalHashTags', $totalHashTags)->with('totalVerificationRequest', $totalVerificationRequest)->with('totalReport', $totalReport)->with('CoinRate', $CoinRate)->with('MyWallet', $MyWallet)->with('totalRedeemRequest', $totalRedeemRequest);
		} else {
			return Redirect::route('login');
		}
	}

	public function logout($flag)
	{
		// Session::flush();
		Session::flush();
		if ($flag == 1) {
			Session::flash('matchResetPassword', 'Password change successfully, Now login by new password...!');
		}
		return redirect()->route('login');
	}
	public function MyProfile()
	{
		if (Session::get('name') && Session::get('is_logged') == 1) {
			$data = Admin::first();
			return view('admin.my-profile')->with('data', $data);
		} else {
			return Redirect::route('login');
		}
	}

	public function updateAdminProfile(Request $request)
	{
		$admin_id = $request->input('admin_id');
		$admin_name = $request->input('admin_name');
		$admin_email = $request->input('email');
		$password = $request->input('password');
		$hdn_profile_image =  $request->input('hdn_profile_image');
		$profile_image = '';
		$data = [];


		if ($request->hasfile('admin_profile')) {
			$file = $request->file('admin_profile');
			$data['admin_profile'] = GlobalFunction::uploadFilToS3($file);
		} else {
			$data['admin_profile'] = $hdn_profile_image;
		}

		$profile_image = $data['admin_profile'];

		$data['admin_name'] = $admin_name;
		$data['admin_email'] = $admin_email;
		if ($password) {
			$data['admin_password'] = $password;
		}

		$update =  Admin::where('admin_id', $admin_id)->update($data);
		if ($update) {
			$response['admin_name'] = $admin_name;
			$response['admin_email'] = $admin_email;
			$response['admin_profile_url'] = url(env('DEFAULT_IMAGE_URL') . $profile_image);
			$response['admin_profile'] = $profile_image;
			$response['status'] = 1;
		} else {
			$response['admin_name'] = "";
			$response['admin_email'] = "";
			$response['admin_profile_url'] = "";
			$response['admin_profile'] = "";
			$response['status'] = 0;
		}
		echo json_encode($response);
	}
}
