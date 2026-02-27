<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\Followers;
use App\Models\FollowRequest;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Jobs\ProcessUserNotificationJob;
use App\Jobs\DeleteUserAccountJob;
use App\Models\UserAuthTokens;
use App\Models\UserBlocks;
use App\Models\UserLinks;
use App\Models\UserMute;
use App\Models\UserRestrict;
use App\Models\UserFavorite;
use App\Models\CloseFriend;
use App\Models\UserHiddenWord;
use App\Models\UsernameRestrictions;
use App\Models\LoginSession;
use App\Models\DataDownloadRequest;
use App\Models\Users;
use App\Models\ConsentLog;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\AnalyticsHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //
    public function updateUser(Request $request){
        $user = Users::find($request->id);

        if($request->username != $user->username){
            $userExists = Users::where('username', $request->username)->exists();
            if($userExists){
                return GlobalFunction::sendSimpleResponse(false,'Username exists already!');
            }
            if(UsernameRestrictions::where('username', $request->username)->exists()){
                return GlobalFunction::sendSimpleResponse(false,'This username is not available.');
            }
        }
        if($request->has('profile_photo')){
            if($user->profile_photo != null){
                GlobalFunction::deleteFile($user->profile_photo);
            }
            $user->profile_photo = GlobalFunction::saveFileAndGivePath($request->profile_photo);
        }
        $user->username = $request->username;
        $user->fullname = $request->fullname;
        $user->user_email = $request->user_email;
        $user->mobile_country_code = $request->mobile_country_code;
        $user->user_mobile_no = $request->user_mobile_no;
        $user->bio = $request->bio;

        if($user->is_dummy == 1){
            if($request->has('is_verify')){
                $user->is_verify = $request->is_verify;
            }
            if($request->has('password')){
                $user->password = $request->password;
            }
        }
       $user->save();
       return GlobalFunction::sendSimpleResponse(true,'User details updated successfully');
    }
    public function users(){

        return view('users');
    }
    public function createDummyUser(){

        return view('createDummyUser');
    }
    public function editUser($id){
        $phoneCountryCodes = GlobalFunction::getPhoneCountryCodes();
        $user = Users::find($id);
        return view('editUser')->with([
            'user'=> $user,
            'phoneCountryCodes'=> $phoneCountryCodes,
        ]);
    }
    public function editDummyUser($id){

        $user = Users::find($id);
        if($user->is_dummy != 1){
            return redirect()->back();
        }
        return view('editDummyUser')->with([
            'user'=> $user
        ]);
    }
    public function addDummyUser(Request $request){
        $user = Users::where('username', $request->username)->first();
        if($user != null){
            return GlobalFunction::sendSimpleResponse(false,'this username is not available');
        }
            $user = new Users;
            $user->fullname = $request->fullname;
            $user->bio = $request->bio;
            $user->identity = GlobalFunction::generateDummyUserIdentity();
            $user->username = $request->username;
            $user->password = $request->password;
            $user->is_verify = $request->is_verify;
            $user->is_dummy = Constants::userDummy;
            $user->profile_photo = GlobalFunction::saveFileAndGivePath($request->profile_photo);
            $user->save();

            return GlobalFunction::sendSimpleResponse(true,'Dummy user added successfully');
    }

    public function changeUserModeratorStatus(Request $request){
        $user = Users::find($request->user_id);
        $user->is_moderator = $request->is_moderator;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'changes applied successfully');
    }

    public function deleteUserLink_Admin(Request $request){
        $link = UserLinks::find($request->id);
        $link->delete();

        return GlobalFunction::sendSimpleResponse(true,'link deleted successfully');
    }

    public function unFollowUser(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $dataUser = GlobalFunction::prepareUserFullData($request->user_id);
        // Self check
        if($user->id == $dataUser->id){
            return GlobalFunction::sendSimpleResponse(false, 'you can not follow/unfollow yourself!');
        }
        $follow = Followers::where([
            'from_user_id'=> $user->id,
            'to_user_id'=> $dataUser->id,
            ])->first();
        if($follow == null){
            return GlobalFunction::sendSimpleResponse(false, 'you are not following this user!');
        }
        $follow->delete();

        GlobalFunction::settleFollowCount($dataUser->id);
        GlobalFunction::settleFollowCount($user->id);

        GlobalFunction::deleteNotifications(Constants::notify_follow_user, $user->id, $user->id);

        AnalyticsHelper::publishEvent('unfollow', $user->id, ['targetUserId' => $dataUser->id]);

        return GlobalFunction::sendSimpleResponse(true, 'unfollow successful');

    }
    public function fetchUserFollowings(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $dataUser = GlobalFunction::prepareUserFullData($request->user_id);

        //  Check if show my following on/off
        if($dataUser->show_my_following == 0){ //1=yes 0=no
            return GlobalFunction::sendSimpleResponse(false, 'this user has turned off his following show.');
        }

         // Block check
         $isBlock = GlobalFunction::checkUserBlock($user->id, $dataUser->id);
         if($isBlock){
             return GlobalFunction::sendSimpleResponse(false, 'you can not continue this action!');
         }


         $query = Followers::where('from_user_id', $dataUser->id)
                ->orderBy('id', 'DESC')
                ->with(['to_user:'.Constants::userPublicFields])
                ->limit($request->limit);
         if($request->has('last_item_id')){
             $query->where('id','<',$request->last_item_id);
         }
        $data = $query ->get();

        foreach($data as $folliwingItem){
            $folliwingItem->to_user->is_following = false;

            $isFollow =  Followers::where([
                    'from_user_id'=> $user->id,
                    'to_user_id'=> $folliwingItem->to_user_id,
                ])->first();
            if($isFollow != null){
                $folliwingItem->to_user->is_following = true;
            }
        }

        return GlobalFunction::sendDataResponse(true, 'following fetched successfully', $data);


    }
    public function fetchUserFollowers(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $dataUser = GlobalFunction::prepareUserFullData($request->user_id);
         // Block check
         $isBlock = GlobalFunction::checkUserBlock($user->id, $dataUser->id);
         if($isBlock){
             return GlobalFunction::sendSimpleResponse(false, 'you can not continue this action!');
         }
         $query = Followers::where('to_user_id', $dataUser->id)
                ->orderBy('id', 'DESC')
                ->with(['from_user:'.Constants::userPublicFields])
                ->limit($request->limit);
         if($request->has('last_item_id')){
             $query->where('id','<',$request->last_item_id);
         }
        $data = $query ->get();

        foreach($data as $followersItem){
            $followersItem->from_user->is_following = false;
            $isFollow =  Followers::where([
                    'from_user_id'=> $user->id,
                    'to_user_id'=> $followersItem->from_user_id,
                ])->first();
            if($isFollow != null){
                $followersItem->from_user->is_following = true;
            }
        }


        return GlobalFunction::sendDataResponse(true, 'followers fetched successfully', $data);


    }
    public function fetchMyFollowings(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $rules = [
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

         $query = Followers::where('from_user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->with(['to_user:'.Constants::userPublicFields])
                ->limit($request->limit);
         if($request->has('last_item_id')){
             $query->where('id','<',$request->last_item_id);
         }
        $data = $query ->get();

        return GlobalFunction::sendDataResponse(true, 'my following fetched successfully', $data);

    }

    public function fetchMyFollowers(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $rules = [
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }

         $query = Followers::where('to_user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->with(['from_user:'.Constants::userPublicFields])
                ->limit($request->limit);
         if($request->has('last_item_id')){
             $query->where('id','<',$request->last_item_id);
         }
        $data = $query ->get();

        foreach($data as $item){
            $item->from_user->is_following = Followers::where([
                'from_user_id'=> $user->id,
                'to_user_id'=> $item->from_user_id,
            ])->exists();
        }

        return GlobalFunction::sendDataResponse(true, 'my followers fetched successfully', $data);

    }
    public function followUser(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $dataUser = GlobalFunction::prepareUserFullData($request->user_id);
        // Self check
        if($user->id == $dataUser->id){
            return GlobalFunction::sendSimpleResponse(false, 'you can not follow yourself!');
        }
        $follow = Followers::where([
            'from_user_id'=> $user->id,
            'to_user_id'=> $dataUser->id,
            ])->first();
        if($follow != null){
            return GlobalFunction::sendSimpleResponse(false, 'you are following this user already!');
        }
        // Block check
        $isBlock = GlobalFunction::checkUserBlock($user->id, $dataUser->id);
        if($isBlock){
            return GlobalFunction::sendSimpleResponse(false, 'you can not follow this user!');
        }

        // Private account: create follow request instead of direct follow
        if ($dataUser->is_private) {
            $existingRequest = FollowRequest::where([
                'from_user_id' => $user->id,
                'to_user_id' => $dataUser->id,
            ])->first();

            if ($existingRequest) {
                if ($existingRequest->status == Constants::followRequestPending) {
                    return GlobalFunction::sendSimpleResponse(false, 'follow request already sent!');
                }
                // If previously rejected, allow re-request
                $existingRequest->status = Constants::followRequestPending;
                $existingRequest->save();
            } else {
                $followRequest = new FollowRequest();
                $followRequest->from_user_id = $user->id;
                $followRequest->to_user_id = $dataUser->id;
                $followRequest->status = Constants::followRequestPending;
                $followRequest->save();
            }

            // Insert Notification Data : Follow Request
            ProcessUserNotificationJob::dispatch(Constants::notify_follow_request, $user->id, $dataUser->id, $user->id);

            return GlobalFunction::sendSimpleResponse(true, 'follow request sent');
        }

        $follow = new Followers();
        $follow->from_user_id = $user->id;
        $follow->to_user_id = $dataUser->id;
        $follow->save();

        GlobalFunction::settleFollowCount($dataUser->id);
        GlobalFunction::settleFollowCount($user->id);

        // Insert Notification Data : Follow User
        ProcessUserNotificationJob::dispatch(Constants::notify_follow_user, $user->id, $dataUser->id, $user->id);

        AnalyticsHelper::publishEvent('follow', $user->id, ['targetUserId' => $dataUser->id]);

        return GlobalFunction::sendSimpleResponse(true, 'follow successful');

    }

    public function fetchUserDetails(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $dataUser = Users::find($request->user_id);

        GlobalFunction::settleUserTotalPostLikesCount($dataUser->id);
        GlobalFunction::settleFollowCount($dataUser->id);

        $dataUser = GlobalFunction::prepareUserFullData($dataUser->id);
         // Check follow
         $dataUser->is_following = Followers::where([
            'from_user_id'=> $user->id,
            'to_user_id'=> $dataUser->id,
        ])->exists();

        // Check follow status
            $following = Followers::where([
                'from_user_id' => $user->id,
                'to_user_id' => $dataUser->id
            ])->exists();

            $follower = Followers::where([
                'from_user_id' => $dataUser->id,
                'to_user_id' => $user->id
            ])->exists();

            if ($following && $follower) {
                $dataUser->follow_status = 3; // Both users follow each other
            } elseif ($following) {
                $dataUser->follow_status = 1; // I am following this user
            } elseif ($follower) {
                $dataUser->follow_status = 2; // The user follows me but I don’t follow back
            } else {
                $dataUser->follow_status = 0; // No follow relationship
            }

        $dataUser->is_block = GlobalFunction::checkUserBlock($user->id, $dataUser->id);

        $muteRecord = GlobalFunction::checkUserMute($user->id, $dataUser->id);
        $dataUser->is_muted = $muteRecord ? true : false;
        $dataUser->mute_posts = $muteRecord ? $muteRecord->mute_posts : false;
        $dataUser->mute_stories = $muteRecord ? $muteRecord->mute_stories : false;

        $dataUser->is_restricted = GlobalFunction::checkUserRestrict($user->id, $dataUser->id);
        $dataUser->is_favorite = GlobalFunction::checkUserFavorite($user->id, $dataUser->id);

        return GlobalFunction::sendDataResponse(true, 'user details fetched successfully', $dataUser);


    }

    public function fetchMyBlockedUsers(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        $items = UserBlocks::where([
            'from_user_id' => $user->id
        ])->with(['to_user:'.Constants::userPublicFields])->get();

        return GlobalFunction::sendDataResponse(true, 'blocked users fetched successfully', $items);

    }

    function searchUsers(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
            $search = GlobalFunction::cleanString($request->keyword);

            $blockedUserIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);

            $query =  Users::whereNotIn('id', $blockedUserIds)
                ->where(function ($query) use ($search) {
                    $query->where('fullname', 'LIKE', "%{$search}%")
                        ->orWhere('username', 'LIKE', "%{$search}%");
                })
                ->select(explode(',',Constants::userPublicFields))
                ->where('is_freez', 0)
                ->orderBy('id', 'DESC')
                ->limit($request->limit);
                if($request->has('last_item_id')){
                    $query->where('id','<',$request->last_item_id);
                }
        $data = $query->get();


        foreach($data as $singleUser){
            $singleUser->is_following = false;
            $isFollow =  Followers::where([
                    'from_user_id'=> $user->id,
                    'to_user_id'=> $singleUser->id,
                ])->first();
            if($isFollow != null){
                $singleUser->is_following = true;
            }
        }

        return GlobalFunction::sendDataResponse(true, 'search users fetched successfully', $data);
    }

    public function unBlockUser(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
           'user_id' => 'required|exists:tbl_users,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $toUser = Users::find($request->user_id);

        if($user->id == $toUser->id){
            return GlobalFunction::sendSimpleResponse(false, 'you can not block/unblock yourself!');
        }
        $item = UserBlocks::where([
            'from_user_id'=> $user->id,
            'to_user_id'=> $toUser->id
        ])->first();
        if($item == null){
            return GlobalFunction::sendSimpleResponse(false, 'this user is not blocked!');
        }
        $item->delete();
        GlobalFunction::clearBlockedUsersCache($user->id);
        GlobalFunction::clearBlockedUsersCache($toUser->id);

        return GlobalFunction::sendSimpleResponse(true, 'user unblocked successfully');

    }

    public function blockUser(Request $request){

        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $toUser = Users::find($request->user_id);

        if($user->id == $toUser->id){
            return GlobalFunction::sendSimpleResponse(false, 'you can not block/unblock yourself!');
        }
        $item = UserBlocks::where([
            'from_user_id'=> $user->id,
            'to_user_id'=> $toUser->id
        ])->first();
        if($item != null){
            return GlobalFunction::sendSimpleResponse(false, 'user is blocked already!');
        }
        $item = new UserBlocks();
        $item->from_user_id = $user->id;
        $item->to_user_id = $toUser->id;
        $item->save();

        // Remove follows in both directions
        Followers::where([
            'from_user_id'=> $toUser->id,
            'to_user_id'=> $user->id,
        ])->delete();

        Followers::where([
            'from_user_id' => $user->id,
            'to_user_id' => $toUser->id,
        ])->delete();

        // Clean up follow requests in both directions
        FollowRequest::where(function ($q) use ($user, $toUser) {
            $q->where('from_user_id', $user->id)->where('to_user_id', $toUser->id);
        })->orWhere(function ($q) use ($user, $toUser) {
            $q->where('from_user_id', $toUser->id)->where('to_user_id', $user->id);
        })->delete();

        // Settle follower/following counts for both users
        GlobalFunction::settleFollowCount($user->id);
        GlobalFunction::settleFollowCount($toUser->id);
        GlobalFunction::clearBlockedUsersCache($user->id);
        GlobalFunction::clearBlockedUsersCache($toUser->id);

        return GlobalFunction::sendSimpleResponse(true, 'user blocked successfully');

    }

    public function muteUser(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        if ($user->id == $request->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot mute yourself!');
        }

        $existing = UserMute::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->first();

        if ($existing) {
            $existing->mute_posts = $request->mute_posts ?? true;
            $existing->mute_stories = $request->mute_stories ?? true;
            $existing->save();
        } else {
            $mute = new UserMute();
            $mute->from_user_id = $user->id;
            $mute->to_user_id = $request->user_id;
            $mute->mute_posts = $request->mute_posts ?? true;
            $mute->mute_stories = $request->mute_stories ?? true;
            $mute->save();
        }

        GlobalFunction::clearMutedUsersCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'User muted successfully');
    }

    public function unMuteUser(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        UserMute::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->delete();

        GlobalFunction::clearMutedUsersCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'User unmuted successfully');
    }

    public function fetchMyMutedUsers(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $mutes = UserMute::where('from_user_id', $user->id)
            ->with(['to_user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'muted users fetched', $mutes);
    }

    // Restrict
    public function restrictUser(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        if ($user->id == $request->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot restrict yourself!');
        }

        $existing = UserRestrict::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'User is already restricted');
        }

        $restrict = new UserRestrict();
        $restrict->from_user_id = $user->id;
        $restrict->to_user_id = $request->user_id;
        $restrict->save();

        GlobalFunction::clearRestrictedUsersCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'User restricted successfully');
    }

    public function unrestrictUser(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        UserRestrict::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->delete();

        GlobalFunction::clearRestrictedUsersCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'User unrestricted successfully');
    }

    public function fetchMyRestrictedUsers(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $restricts = UserRestrict::where('from_user_id', $user->id)
            ->with(['to_user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'restricted users fetched', $restricts);
    }

    public function addToFavorites(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        if ($user->id == $request->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot add yourself to favorites!');
        }

        $existing = UserFavorite::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'User is already in favorites');
        }

        $favorite = new UserFavorite();
        $favorite->from_user_id = $user->id;
        $favorite->to_user_id = $request->user_id;
        $favorite->save();

        GlobalFunction::clearFavoriteUsersCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'User added to favorites');
    }

    public function removeFromFavorites(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        UserFavorite::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->delete();

        GlobalFunction::clearFavoriteUsersCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'User removed from favorites');
    }

    public function fetchMyFavorites(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $favorites = UserFavorite::where('from_user_id', $user->id)
            ->with(['to_user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'favorite users fetched', $favorites);
    }

    // Close Friends
    public function addCloseFriend(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        if ($user->id == $request->user_id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot add yourself!');
        }

        $existing = CloseFriend::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'User is already in close friends');
        }

        $cf = new CloseFriend();
        $cf->from_user_id = $user->id;
        $cf->to_user_id = $request->user_id;
        $cf->save();

        Cache::forget("close_friends:{$user->id}");
        return GlobalFunction::sendSimpleResponse(true, 'User added to close friends');
    }

    public function removeCloseFriend(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        CloseFriend::where('from_user_id', $user->id)
            ->where('to_user_id', $request->user_id)->delete();

        Cache::forget("close_friends:{$user->id}");
        return GlobalFunction::sendSimpleResponse(true, 'User removed from close friends');
    }

    public function fetchMyCloseFriends(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $closeFriends = CloseFriend::where('from_user_id', $user->id)
            ->with(['to_user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'close friends fetched', $closeFriends);
    }

    public function addHiddenWord(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['word' => 'required|string|max:100'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $word = mb_strtolower(trim($request->word));

        $existing = UserHiddenWord::where('user_id', $user->id)
            ->where('word', $word)->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Word already in your hidden words list');
        }

        UserHiddenWord::create([
            'user_id' => $user->id,
            'word' => $word,
        ]);

        GlobalFunction::clearHiddenWordsCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'Word added to hidden words');
    }

    public function removeHiddenWord(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['word' => 'required|string'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $word = mb_strtolower(trim($request->word));

        UserHiddenWord::where('user_id', $user->id)
            ->where('word', $word)->delete();

        GlobalFunction::clearHiddenWordsCache($user->id);
        return GlobalFunction::sendSimpleResponse(true, 'Word removed from hidden words');
    }

    public function fetchHiddenWords(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $words = UserHiddenWord::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->pluck('word');

        return GlobalFunction::sendDataResponse(true, 'hidden words fetched', $words);
    }

    public function viewUserDetails($id){

        $user = GlobalFunction::prepareUserFullData($id);
        $baseUrl = GlobalFunction::getItemBaseUrl();
        $user->levelNumber = GlobalFunction::determineUserLevel($user->id);

        return view('viewUserDetails',[
            'user'=> $user,
            'baseUrl'=> $baseUrl,
        ]);
    }
    public function listDummyUsers(Request $request)
    {
        $query = Users::query();
        $query->where('is_dummy', 1);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('fullname', 'LIKE', "%{$searchValue}%")
                ->orWhere('username', 'LIKE', "%{$searchValue}%")
                ->orWhere('identity', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $userProfileCard = GlobalFunction::createUserDetailsColumn($item->id);

            $freeze = GlobalFunction::createUserFreezeSwitch($item,'dummy');

            $moderator = GlobalFunction::createUserModeratorSwitch($item,'dummy');

            $userDetailsUrl = route('viewUserDetails', $item->id);
            $editDummyUserUrl = route('editDummyUser', $item->id);

            $view = "<a href='$userDetailsUrl'
                          rel='{$item->id}'
                          class='action-btn d-flex align-items-center justify-content-center btn border rounded-2 text-info ms-1'>
                            <i class='ri-eye-line'></i>
                        </a>";
            $edit = "<a href='$editDummyUserUrl'
                          rel='{$item->id}'
                          class='action-btn d-flex align-items-center justify-content-center btn border rounded-2 text-info ms-1'>
                            <i class='uil-pen'></i>
                        </a>";
            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}{$edit}{$delete}</span>";


            $identity = "<h5>{$item->identity}</h5>";
            $password = "<p class='m-0'>{$item->password}</p>";
            $identity_password = '<div class="">'.$identity.$password.'</div>';

            return [
                $userProfileCard,
                $identity_password,
                $freeze,
                $moderator,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }
    public function listAllModerators(Request $request)
    {
        $query = Users::query();
        $query->where('is_moderator', 1);
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('fullname', 'LIKE', "%{$searchValue}%")
                ->orWhere('username', 'LIKE', "%{$searchValue}%")
                ->orWhere('identity', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $userProfileCard = GlobalFunction::createUserDetailsColumn($item->id);

            $realOrFake = GlobalFunction::createUserTypeBadge($item->id);

            $freeze = GlobalFunction::createUserFreezeSwitch($item,'moderators');

            $moderator = GlobalFunction::createUserModeratorSwitch($item,'moderators');

            $userDetailsUrl = route('viewUserDetails', $item->id);

            $view = "<a href='$userDetailsUrl'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-info ms-1'>
                            <i class='ri-eye-line'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}</span>";

            return [
                $userProfileCard,
                $realOrFake,
                $item->identity,
                $freeze,
                $moderator,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }
    public function listAllUsers(Request $request)
    {
        $query = Users::query();
        $totalData = $query->count();

        $columns = ['id'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('fullname', 'LIKE', "%{$searchValue}%")
                ->orWhere('username', 'LIKE', "%{$searchValue}%")
                ->orWhere('identity', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {

            $userProfileCard = GlobalFunction::createUserDetailsColumn($item->id);

            $realOrFake = GlobalFunction::createUserTypeBadge($item->id);

            $freeze = GlobalFunction::createUserFreezeSwitch($item, 'all');

            $moderator = GlobalFunction::createUserModeratorSwitch($item,'all');

            $userDetailsUrl = route('viewUserDetails', $item->id);

            $view = "<a href='$userDetailsUrl'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-info ms-1'>
                            <i class='ri-eye-line'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$view}</span>";

            return [
                $userProfileCard,
                $realOrFake,
                $item->identity,
                $freeze,
                $moderator,
                $action
            ];
        });

        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];

        return response()->json($json_data);
    }
    public function userFreezeUnfreeze(Request $request){
        $user = Users::find($request->user_id);
        $user->is_freez = $request->is_freez;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'Task successful');
    }
    public function updateLastUsedAt(Request $request){

        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        $user->app_last_used_at = Carbon::now();
        $user->save();

        AnalyticsHelper::publishEvent('app_open', $user->id);

        return GlobalFunction::sendSimpleResponse(true, 'last log in updated successfully');

    }

    public function checkUsernameAvailability(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::where('username', $request->username)->first();
        if($user){
            return GlobalFunction::sendSimpleResponse(false, 'username not available!');
        }

        if(UsernameRestrictions::where('username', $request->username)->exists()){
            return GlobalFunction::sendSimpleResponse(false, 'This username is not available.');
        }

        return GlobalFunction::sendSimpleResponse(true, 'username available!');

    }

    public function editeUserLink(Request $request){

        $validator = Validator::make($request->all(), [
            'link_id' => 'required|exists:user_links,id',
            'title' => 'required',
            'url' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => "this user is freezed!"]);
        }
        $link = UserLinks::find($request->link_id);
        if(!$link){
            return GlobalFunction::sendSimpleResponse(false, 'Link not found!');
        }
        if($link->user_id != $user->id){
            return GlobalFunction::sendSimpleResponse(false, 'this link is not owned by this user!');
        }
        $link->title = $request->title;
        $link->url = $request->url;
        $link->save();

        return GlobalFunction::sendDataResponse(true, 'user link edited successfully!', $user->links);

    }
    public function deleteUserLink(Request $request){

        $validator = Validator::make($request->all(), [
            'link_id' => 'required|exists:user_links,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => "this user is freezed!"]);
        }
        $link = UserLinks::find($request->link_id);
        if(!$link){
            return GlobalFunction::sendSimpleResponse(false, 'Link not found!');
        }
        if($link->user_id != $user->id){
            return GlobalFunction::sendSimpleResponse(false, 'this link is not owned by this user!');
        }
        $link->delete();

        return GlobalFunction::sendDataResponse(true, 'user link deleted successfully!', $user->links);

    }
    public function deleteMyAccount(Request $request){
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user) {
           DeleteUserAccountJob::dispatch($user->id);
        }
         return GlobalFunction::sendSimpleResponse(true, 'account deleted successfully');
    }
    public function deleteDummyUser(Request $request){
        $user = Users::find($request->id);
        if ($user) {
           DeleteUserAccountJob::dispatch($user->id);
        }
         return GlobalFunction::sendSimpleResponse(true, 'User deleted successfully');
    }


    public function addUserLink(Request $request){

        $validator = Validator::make($request->all(), [
            'url' => 'required',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => "this user is freezed!"]);
        }

        $link = new UserLinks();
        $link->user_id = $user->id;
        $link->title = $request->title;
        $link->url = $request->url;
        $link->save();

        return GlobalFunction::sendDataResponse(true, 'user link added successfully!', $user->links);

    }

    public function updateUserDetails(Request $request)
    {
        $token = $request->header('authtoken');

        // Validate user token and fetch user
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => "this user is freezed!"]);
        }

        // Define fields to update
        $updatableFields = [
            'fullname',
            'user_email',
            'user_mobile_no',
            'mobile_country_code',
            'device_token',
            'bio',
            'country',
            'countryCode',
            'region',
            'regionName',
            'city',
            'lon',
            'lat',
            'timezone',
            'notify_post_like',
            'notify_post_comment',
            'notify_follow',
            'notify_mention',
            'notify_gift_received',
            'notify_chat',
            'receive_message',
            'show_my_following',
            'who_can_view_post',
            'saved_music_ids',
            'app_language',
            'is_verify',
            'is_private',
            'interest_ids',
            'hide_others_like_count',
            'comment_approval_enabled',
            'quiet_mode_enabled',
            'quiet_mode_until',
            'quiet_mode_auto_reply',
            'sensitive_content_level',
            'pronouns',
        ];

        // Update user fields dynamically
        foreach ($updatableFields as $field) {
            if ($request->has($field)) {
                $user->$field = $request->$field;
            }
        }
        // Handle profile photo separately
        if ($request->has('profile_photo')) {
            if ($user->profile_photo) {
                GlobalFunction::deleteFile($user->profile_photo);
            }
            $user->profile_photo = GlobalFunction::saveFileAndGivePath($request->profile_photo);
        }
        // Handle Username
        if ($request->has('username')) {
            $user2 = Users::where('username', $request->username)->first();
            if($user2 && $user2->id != $user->id){
                return GlobalFunction::sendSimpleResponse(false, 'username is not available!');
            }
            $restriction = UsernameRestrictions::where('username', $request->username)->first();
            if($restriction){
                return GlobalFunction::sendSimpleResponse(false, 'username is not available!');
            }
            $user->username = $request->username;
        }

        // Save updated user details
        $user->save();
        $user = GlobalFunction::prepareUserFullData($user->id);
        return GlobalFunction::sendDataResponse(true, 'User details updated successfully', $user);
    }


    function logInUser(Request $request){
        $validator = Validator::make($request->all(), [
            'fullname' => 'required',
            'identity' => 'required',
            'device_token' => 'required',
            'device' => 'required',
            'login_method' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::where('identity', $request->identity)->first();

        if ($user == null) {
            $user = new Users;
            $user->fullname = GlobalFunction::cleanString($request->fullname);
            $user->identity = $request->identity;
            $user->device_token = $request->device_token;
            $user->device = $request->device;
            $user->login_method = $request->login_method;
            $user->username = GlobalFunction::generateUsername($user->fullname);

            // Enhanced device capture
            if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
            if ($request->has('device_model')) $user->device_model = $request->device_model;
            if ($request->has('device_os')) $user->device_os = $request->device_os;
            if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
            if ($request->has('device_carrier')) $user->device_carrier = $request->device_carrier;

            if ($request->has('profile_photo')) {
                $user->profile_photo = GlobalFunction::saveFileAndGivePath($request->profile_photo);
            }

            $settings = GlobalSettings::getCached();
            if($settings->registration_bonus_status == 1){
                $user->coin_wallet = $settings->registration_bonus_amount;
                $user->coin_collected_lifetime = $settings->registration_bonus_amount;
            }

            $user->save();

            $token = GlobalFunction::generateUserAuthToken($user);

            // Record login session
            $this->recordLoginSession($user, $request, $token->auth_token ?? null);

            $user =  GlobalFunction::prepareUserFullData($user->id);
            $user->new_register = true;
            $user->token = $token;

            $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

            return GlobalFunction::sendDataResponse(true,'Data Fetch Successful!', $user);

        } else {
            $user->device_token = $request->device_token;
            $user->device = $request->device;
            $user->login_method = $request->login_method;

            // Enhanced device capture on re-login
            if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
            if ($request->has('device_model')) $user->device_model = $request->device_model;
            if ($request->has('device_os')) $user->device_os = $request->device_os;
            if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
            if ($request->has('device_carrier')) $user->device_carrier = $request->device_carrier;

            $user->save();

            // Check if 2FA is enabled
            if ($user->two_fa_enabled) {
                $tempToken = TwoFactorController::generateTempToken($user->id);
                return [
                    'status' => true,
                    'message' => '2FA verification required',
                    'data' => [
                        'require_totp' => true,
                        'temp_2fa_token' => $tempToken,
                        'user_id' => $user->id,
                    ],
                ];
            }

            $token = GlobalFunction::generateUserAuthToken($user);

            // Record login session
            $this->recordLoginSession($user, $request, $token->auth_token ?? null);

            $user = GlobalFunction::prepareUserFullData($user->id);
            $user->new_register = false;
            $user->token = $token;
            $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

            return GlobalFunction::sendDataResponse(true, 'Data Fetch Successful!', $user);
        }
    }
    function logInFakeUser(Request $request){
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
            'password' => 'required',
            'device_token' => 'required',
            'device' => 'required',
            'login_method' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::where('identity', $request->identity)
        ->where('password', $request->password)
        ->where('is_dummy', 1)
        ->first();

        if ($user != null) {
            $user->device_token = $request->device_token;
            $user->device = $request->device;
            $user->login_method = $request->login_method;
            $user->save();

            // Check if 2FA is enabled
            if ($user->two_fa_enabled) {
                $tempToken = TwoFactorController::generateTempToken($user->id);
                return [
                    'status' => true,
                    'message' => '2FA verification required',
                    'data' => [
                        'require_totp' => true,
                        'temp_2fa_token' => $tempToken,
                        'user_id' => $user->id,
                    ],
                ];
            }

            $token = GlobalFunction::generateUserAuthToken($user);

            // Record login session
            $this->recordLoginSession($user, $request, $token->auth_token ?? null);

            $user =  GlobalFunction::prepareUserFullData($user->id);
            $user->new_register = false;
            $user->token = $token;

            $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

            return GlobalFunction::sendDataResponse(true,'Data Fetch Successful!', $user);

        } else {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid Credentials');
        }
    }
    function logOutUser(Request $request){
        // Validate user token and fetch user
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        $user->device_token = null;
        $authToken = UserAuthTokens::where('user_id', $user->id)->first();
        $authToken->delete();
        $user->save();

        // Mark current login session as not current
        LoginSession::where('user_id', $user->id)->where('is_current', true)->update(['is_current' => false]);

        return GlobalFunction::sendSimpleResponse(true, 'Log out Successful!');
    }

    // ===================== CUSTOM AUTH ENDPOINTS =====================

    /**
     * Register a new user with email and password.
     */
    function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'device_token' => 'required',
            'device' => 'required',
            'date_of_birth' => 'required|date|before:today',
            'terms_accepted' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Normalize email to lowercase before any DB operations
        $email = strtolower(trim($request->email));

        // Check if email already exists (case-insensitive)
        $existing = Users::whereRaw('LOWER(identity) = ?', [$email])->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'An account with this email already exists.');
        }

        // Age verification
        $dob = Carbon::parse($request->date_of_birth);
        $age = $dob->age;
        $settings = GlobalSettings::getCached();
        $minimumAge = $settings->minimum_age ?? 13;

        if ($age < $minimumAge) {
            return GlobalFunction::sendSimpleResponse(false, "You must be at least $minimumAge years old to create an account.");
        }

        try {
            return \DB::transaction(function () use ($request, $dob, $age, $settings) {
                $user = new Users;
                $user->fullname = GlobalFunction::cleanString($request->fullname);
                $user->identity = $email; // already normalized to lowercase
                $user->password_hash = Hash::make($request->password);
                $user->device_token = $request->device_token;
                $user->device = $request->device;
                $user->login_method = 'email';
                $user->username = GlobalFunction::generateUsername($user->fullname);
                $user->date_of_birth = $dob->toDateString();
                $user->is_minor = $age < 18;

                // Enhanced device capture
                if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
                if ($request->has('device_model')) $user->device_model = $request->device_model;
                if ($request->has('device_os')) $user->device_os = $request->device_os;
                if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
                if ($request->has('device_carrier')) $user->device_carrier = $request->device_carrier;

                // Registration bonus
                if ($settings->registration_bonus_status == 1) {
                    $user->coin_wallet = $settings->registration_bonus_amount;
                    $user->coin_collected_lifetime = $settings->registration_bonus_amount;
                }

                // Consent tracking
                $termsVersion = $settings->terms_version ?? '1.0';
                $privacyVersion = $settings->privacy_version ?? '1.0';
                $user->terms_accepted_at = Carbon::now();
                $user->terms_version = $termsVersion;
                $user->privacy_accepted_at = Carbon::now();
                $user->privacy_version = $privacyVersion;

                $user->save();

                // Record consent in audit log
                $ip = $request->ip();
                $ua = $request->userAgent();
                ConsentLog::recordConsent($user->id, 'terms', $termsVersion, 'accepted', $ip, $ua);
                ConsentLog::recordConsent($user->id, 'privacy', $privacyVersion, 'accepted', $ip, $ua);

                // Send email verification if enabled
                if ($settings->email_verification_enabled) {
                    $code = GlobalFunction::generateOTP();
                    $user->email_verification_code = $code;
                    $user->email_verification_expires_at = Carbon::now()->addMinutes(10);
                    $user->save();

                    GlobalFunction::sendVerificationEmail($user, $code);

                    return response()->json([
                        'status' => true,
                        'message' => 'Account created. Please verify your email.',
                        'data' => [
                            'require_email_verification' => true,
                            'user_id' => $user->id,
                            'email' => $user->identity,
                        ],
                    ]);
                }

                // No verification needed — log in directly
                $token = GlobalFunction::generateUserAuthToken($user);
                $this->recordLoginSession($user, $request, $token->auth_token ?? null);

                $user = GlobalFunction::prepareUserFullData($user->id);
                $user->new_register = true;
                $user->token = $token;
                $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

                return GlobalFunction::sendDataResponse(true, 'Registration Successful!', $user);
            });
        } catch (\Exception $e) {
            \Log::error('Registration failed: ' . $e->getMessage());
            return GlobalFunction::sendSimpleResponse(false, 'Registration failed. Please try again.');
        }
    }

    /**
     * Login with email and password.
     */
    function loginWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|string|max:255',  // accepts email OR username
            'password'     => 'required|string',
            'device_token' => 'required',
            'device'       => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Normalize to lowercase for case-insensitive matching
        $login = strtolower(trim($request->email));

        // Validate format depending on whether it looks like an email
        if (str_contains($login, '@')) {
            if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return GlobalFunction::sendSimpleResponse(false, 'Please enter a valid email address.');
            }
        } else {
            // Username: 3–30 chars, alphanumeric + dots + underscores only
            if (strlen($login) < 3 || strlen($login) > 30) {
                return GlobalFunction::sendSimpleResponse(false, 'Username must be between 3 and 30 characters.');
            }
            if (!preg_match('/^[a-z0-9._]+$/', $login)) {
                return GlobalFunction::sendSimpleResponse(false, 'Username can only contain letters, numbers, dots and underscores.');
            }
        }

        // Look up by email (identity) OR username — both case-insensitive
        $user = Users::whereRaw('LOWER(identity) = ?', [$login])
            ->orWhereRaw('LOWER(username) = ?', [$login])
            ->first();

        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'No account found with this email or username.');
        }

        // Check password — support both bcrypt hash and legacy plaintext (for dummy users)
        if ($user->password_hash) {
            if (!Hash::check($request->password, $user->password_hash)) {
                return GlobalFunction::sendSimpleResponse(false, 'Invalid credentials. Please check your email/username and password.');
            }
        } elseif ($user->is_dummy == 1 && $user->password) {
            // Legacy dummy user with plaintext password
            if ($user->password !== $request->password) {
                return GlobalFunction::sendSimpleResponse(false, 'Invalid credentials. Please check your email/username and password.');
            }
        } else {
            // User registered via social login — no password set
            return GlobalFunction::sendSimpleResponse(false, 'This account was created via social login. Please use Google or Apple sign-in.');
        }

        // Check if email verification is required
        $settings = GlobalSettings::getCached();
        if ($settings->email_verification_enabled && !$user->email_verified_at) {
            // Resend verification code
            $code = GlobalFunction::generateOTP();
            $user->email_verification_code = $code;
            $user->email_verification_expires_at = Carbon::now()->addMinutes(10);
            $user->save();

            GlobalFunction::sendVerificationEmail($user, $code);

            return response()->json([
                'status' => true,
                'message' => 'Please verify your email first.',
                'data' => [
                    'require_email_verification' => true,
                    'user_id' => $user->id,
                    'email' => $user->identity,
                ],
            ]);
        }

        // Update device info
        $user->device_token = $request->device_token;
        $user->device = $request->device;
        $user->login_method = 'email';
        if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
        if ($request->has('device_model')) $user->device_model = $request->device_model;
        if ($request->has('device_os')) $user->device_os = $request->device_os;
        if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
        if ($request->has('device_carrier')) $user->device_carrier = $request->device_carrier;
        $user->save();

        // Check 2FA
        if ($user->two_fa_enabled) {
            $tempToken = TwoFactorController::generateTempToken($user->id);
            return [
                'status' => true,
                'message' => '2FA verification required',
                'data' => [
                    'require_totp' => true,
                    'temp_2fa_token' => $tempToken,
                    'user_id' => $user->id,
                ],
            ];
        }

        $token = GlobalFunction::generateUserAuthToken($user);
        $this->recordLoginSession($user, $request, $token->auth_token ?? null);

        $user = GlobalFunction::prepareUserFullData($user->id);
        $user->new_register = false;
        $user->token = $token;
        $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

        return GlobalFunction::sendDataResponse(true, 'Login Successful!', $user);
    }

    /**
     * Login/Register with Google. Receives Google ID token and verifies it server-side.
     */
    function loginWithGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'device_token' => 'required',
            'device' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Verify Google ID token
        try {
            $client = new \Google\Client();
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return GlobalFunction::sendSimpleResponse(false, 'Invalid Google token.');
            }

            $googleEmail = $payload['email'];
            $googleName = $payload['name'] ?? '';
            $googlePhoto = $payload['picture'] ?? null;

        } catch (\Exception $e) {
            Log::error('Google token verification failed: ' . $e->getMessage());
            return GlobalFunction::sendSimpleResponse(false, 'Google authentication failed.');
        }

        $user = Users::where('identity', $googleEmail)->first();
        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = new Users;
            $user->fullname = GlobalFunction::cleanString($googleName);
            $user->identity = $googleEmail;
            $user->email_verified_at = Carbon::now(); // Google emails are verified
            $user->username = GlobalFunction::generateUsername($user->fullname);

            $settings = GlobalSettings::getCached();
            if ($settings->registration_bonus_status == 1) {
                $user->coin_wallet = $settings->registration_bonus_amount;
                $user->coin_collected_lifetime = $settings->registration_bonus_amount;
            }
        }

        $user->device_token = $request->device_token;
        $user->device = $request->device;
        $user->login_method = 'google';
        if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
        if ($request->has('device_model')) $user->device_model = $request->device_model;
        if ($request->has('device_os')) $user->device_os = $request->device_os;
        if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
        if ($request->has('device_carrier')) $user->device_carrier = $request->device_carrier;
        $user->save();

        // Check 2FA for existing users
        if (!$isNewUser && $user->two_fa_enabled) {
            $tempToken = TwoFactorController::generateTempToken($user->id);
            return [
                'status' => true,
                'message' => '2FA verification required',
                'data' => [
                    'require_totp' => true,
                    'temp_2fa_token' => $tempToken,
                    'user_id' => $user->id,
                ],
            ];
        }

        $token = GlobalFunction::generateUserAuthToken($user);
        $this->recordLoginSession($user, $request, $token->auth_token ?? null);

        $user = GlobalFunction::prepareUserFullData($user->id);
        $user->new_register = $isNewUser;
        $user->token = $token;
        $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

        return GlobalFunction::sendDataResponse(true, 'Login Successful!', $user);
    }

    /**
     * Login/Register with Apple. Receives Apple identity token and verifies it server-side.
     */
    function loginWithApple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identity_token' => 'required|string',
            'authorization_code' => 'required|string',
            'device_token' => 'required',
            'device' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Decode Apple identity token (JWT) to get email and sub
        try {
            $tokenParts = explode('.', $request->identity_token);
            if (count($tokenParts) !== 3) {
                return GlobalFunction::sendSimpleResponse(false, 'Invalid Apple token format.');
            }

            $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);

            if (!$payload || !isset($payload['sub'])) {
                return GlobalFunction::sendSimpleResponse(false, 'Invalid Apple token.');
            }

            // Verify issuer and audience
            if (($payload['iss'] ?? '') !== 'https://appleid.apple.com') {
                return GlobalFunction::sendSimpleResponse(false, 'Invalid Apple token issuer.');
            }

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return GlobalFunction::sendSimpleResponse(false, 'Apple token expired.');
            }

            $appleSub = $payload['sub'];
            $appleEmail = $payload['email'] ?? null;
            $appleFullname = $request->fullname ?? '';

        } catch (\Exception $e) {
            Log::error('Apple token verification failed: ' . $e->getMessage());
            return GlobalFunction::sendSimpleResponse(false, 'Apple authentication failed.');
        }

        // Look up user by Apple sub ID first, then by email
        $user = Users::where('identity', $appleSub)->first();
        if (!$user && $appleEmail) {
            $user = Users::where('identity', $appleEmail)->first();
        }

        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = new Users;
            $user->fullname = GlobalFunction::cleanString($appleFullname ?: 'Apple User');
            // Use email as identity if available, otherwise use Apple sub ID
            $user->identity = $appleEmail ?: $appleSub;
            $user->email_verified_at = Carbon::now(); // Apple emails are verified
            $user->username = GlobalFunction::generateUsername($user->fullname);

            $settings = GlobalSettings::getCached();
            if ($settings->registration_bonus_status == 1) {
                $user->coin_wallet = $settings->registration_bonus_amount;
                $user->coin_collected_lifetime = $settings->registration_bonus_amount;
            }
        }

        $user->device_token = $request->device_token;
        $user->device = $request->device;
        $user->login_method = 'apple';
        if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
        if ($request->has('device_model')) $user->device_model = $request->device_model;
        if ($request->has('device_os')) $user->device_os = $request->device_os;
        if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
        if ($request->has('device_carrier')) $user->device_carrier = $request->device_carrier;
        $user->save();

        // Check 2FA for existing users
        if (!$isNewUser && $user->two_fa_enabled) {
            $tempToken = TwoFactorController::generateTempToken($user->id);
            return [
                'status' => true,
                'message' => '2FA verification required',
                'data' => [
                    'require_totp' => true,
                    'temp_2fa_token' => $tempToken,
                    'user_id' => $user->id,
                ],
            ];
        }

        $token = GlobalFunction::generateUserAuthToken($user);
        $this->recordLoginSession($user, $request, $token->auth_token ?? null);

        $user = GlobalFunction::prepareUserFullData($user->id);
        $user->new_register = $isNewUser;
        $user->token = $token;
        $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

        return GlobalFunction::sendDataResponse(true, 'Login Successful!', $user);
    }

    /**
     * Verify email with OTP code.
     */
    function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'code' => 'required|string|size:6',
            'device_token' => 'required',
            'device' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::find($request->user_id);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found.');
        }

        if ($user->email_verified_at) {
            return GlobalFunction::sendSimpleResponse(false, 'Email is already verified.');
        }

        if ($user->email_verification_code !== $request->code) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid verification code.');
        }

        if ($user->email_verification_expires_at && Carbon::parse($user->email_verification_expires_at)->isPast()) {
            return GlobalFunction::sendSimpleResponse(false, 'Verification code has expired. Please request a new one.');
        }

        // Mark as verified
        $user->email_verified_at = Carbon::now();
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->device_token = $request->device_token;
        $user->device = $request->device;
        if ($request->has('device_brand')) $user->device_brand = $request->device_brand;
        if ($request->has('device_model')) $user->device_model = $request->device_model;
        if ($request->has('device_os')) $user->device_os = $request->device_os;
        if ($request->has('device_os_version')) $user->device_os_version = $request->device_os_version;
        $user->save();

        // Check 2FA
        if ($user->two_fa_enabled) {
            $tempToken = TwoFactorController::generateTempToken($user->id);
            return [
                'status' => true,
                'message' => '2FA verification required',
                'data' => [
                    'require_totp' => true,
                    'temp_2fa_token' => $tempToken,
                    'user_id' => $user->id,
                ],
            ];
        }

        $token = GlobalFunction::generateUserAuthToken($user);
        $this->recordLoginSession($user, $request, $token->auth_token ?? null);

        $user = GlobalFunction::prepareUserFullData($user->id);
        $user->new_register = true;
        $user->token = $token;
        $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

        return GlobalFunction::sendDataResponse(true, 'Email verified successfully!', $user);
    }

    /**
     * Resend email verification code.
     */
    function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::find($request->user_id);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found.');
        }

        if ($user->email_verified_at) {
            return GlobalFunction::sendSimpleResponse(false, 'Email is already verified.');
        }

        $code = GlobalFunction::generateOTP();
        $user->email_verification_code = $code;
        $user->email_verification_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        $sent = GlobalFunction::sendVerificationEmail($user, $code);

        if ($sent) {
            return GlobalFunction::sendSimpleResponse(true, 'Verification code sent to your email.');
        } else {
            return GlobalFunction::sendSimpleResponse(false, 'Failed to send verification email. Please try again.');
        }
    }

    /**
     * Request password reset — sends OTP to email.
     */
    function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::where('identity', $request->email)->first();
        if (!$user) {
            // Don't reveal whether email exists
            return GlobalFunction::sendSimpleResponse(true, 'If an account exists with this email, a reset code has been sent.');
        }

        // Check if user has a password (social-only accounts can't reset)
        if (!$user->password_hash && !($user->is_dummy == 1 && $user->password)) {
            return GlobalFunction::sendSimpleResponse(false, 'This account uses social login. Please use Google or Apple sign-in.');
        }

        $code = GlobalFunction::generateOTP();
        $user->password_reset_code = $code;
        $user->password_reset_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        $sent = GlobalFunction::sendPasswordResetEmail($user, $code);

        return GlobalFunction::sendSimpleResponse(true, 'If an account exists with this email, a reset code has been sent.');
    }

    /**
     * Verify password reset code.
     */
    function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::where('identity', $request->email)->first();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid email or code.');
        }

        if ($user->password_reset_code !== $request->code) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid reset code.');
        }

        if ($user->password_reset_expires_at && Carbon::parse($user->password_reset_expires_at)->isPast()) {
            return GlobalFunction::sendSimpleResponse(false, 'Reset code has expired. Please request a new one.');
        }

        return GlobalFunction::sendSimpleResponse(true, 'Code verified. You can now set a new password.');
    }

    /**
     * Reset password with verified code.
     */
    function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::where('identity', $request->email)->first();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid email or code.');
        }

        if ($user->password_reset_code !== $request->code) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid reset code.');
        }

        if ($user->password_reset_expires_at && Carbon::parse($user->password_reset_expires_at)->isPast()) {
            return GlobalFunction::sendSimpleResponse(false, 'Reset code has expired. Please request a new one.');
        }

        $user->password_hash = Hash::make($request->new_password);
        $user->password_reset_code = null;
        $user->password_reset_expires_at = null;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'Password reset successful. You can now log in.');
    }

    // ===================== END CUSTOM AUTH ENDPOINTS =====================

    // Follow Request endpoints
    public function fetchFollowRequests(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $rules = [
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $query = FollowRequest::where('to_user_id', $user->id)
            ->where('status', Constants::followRequestPending)
            ->orderBy('id', 'DESC')
            ->with(['from_user:' . Constants::userPublicFields])
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $data = $query->get();

        return GlobalFunction::sendDataResponse(true, 'follow requests fetched successfully', $data);
    }

    public function acceptFollowRequest(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $rules = [
            'request_id' => 'required|exists:follow_requests,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $followRequest = FollowRequest::find($request->request_id);
        if ($followRequest->to_user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'this request is not for you!');
        }
        if ($followRequest->status != Constants::followRequestPending) {
            return GlobalFunction::sendSimpleResponse(false, 'this request is already processed!');
        }

        // Accept: create follow relationship
        $followRequest->status = Constants::followRequestAccepted;
        $followRequest->save();

        $follow = new Followers();
        $follow->from_user_id = $followRequest->from_user_id;
        $follow->to_user_id = $followRequest->to_user_id;
        $follow->save();

        GlobalFunction::settleFollowCount($followRequest->to_user_id);
        GlobalFunction::settleFollowCount($followRequest->from_user_id);

        // Notify the requester
        ProcessUserNotificationJob::dispatch(Constants::notify_follow_user, $user->id, $followRequest->from_user_id, $user->id);

        return GlobalFunction::sendSimpleResponse(true, 'follow request accepted');
    }

    public function rejectFollowRequest(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $rules = [
            'request_id' => 'required|exists:follow_requests,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $followRequest = FollowRequest::find($request->request_id);
        if ($followRequest->to_user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'this request is not for you!');
        }

        $followRequest->status = Constants::followRequestRejected;
        $followRequest->save();

        return GlobalFunction::sendSimpleResponse(true, 'follow request rejected');
    }

    // --- Login Activity ---

    private function recordLoginSession($user, Request $request, ?string $authToken = null)
    {
        // Mark all existing sessions as not current
        LoginSession::where('user_id', $user->id)->where('is_current', true)->update(['is_current' => false]);

        // Create new session record
        $session = new LoginSession();
        $session->user_id = $user->id;
        $session->device = $request->device ?? null;
        $session->device_brand = $request->device_brand ?? $user->device_brand ?? null;
        $session->device_model = $request->device_model ?? $user->device_model ?? null;
        $session->device_os = $request->device_os ?? $user->device_os ?? null;
        $session->device_os_version = $request->device_os_version ?? $user->device_os_version ?? null;
        $session->ip_address = $request->ip();
        $session->login_method = $request->login_method ?? null;
        $session->is_current = true;
        $session->logged_in_at = now();
        $session->last_active_at = now();
        $session->save();

        // Keep only last 50 sessions per user
        $oldSessions = LoginSession::where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->skip(50)
            ->take(1000)
            ->pluck('id');
        if ($oldSessions->isNotEmpty()) {
            LoginSession::whereIn('id', $oldSessions)->delete();
        }

        // Store multi-account session if device_id is provided
        if ($authToken && $request->device_id) {
            AccountSessionController::storeSession($user->id, $authToken, $request->device_id);
        }
    }

    public function fetchLoginSessions(Request $request)
    {
        $user = $request->authUser;

        $sessions = LoginSession::where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->limit(50)
            ->get();

        return GlobalFunction::sendDataResponse(true, 'Login sessions fetched', $sessions);
    }

    public function logOutSession(Request $request)
    {
        $user = $request->authUser;

        $validator = Validator::make($request->all(), [
            'session_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $session = LoginSession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return GlobalFunction::sendSimpleResponse(false, 'Session not found');
        }

        if ($session->is_current) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot log out current session from here');
        }

        $session->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Session removed successfully');
    }

    // --- Data Download ---

    public function requestDataDownload(Request $request)
    {
        $user = $request->authUser;

        // Check if there's already a pending/processing request
        $existing = DataDownloadRequest::where('user_id', $user->id)
            ->whereIn('status', [DataDownloadRequest::STATUS_PENDING, DataDownloadRequest::STATUS_PROCESSING])
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'You already have a pending data export request');
        }

        // Check cooldown — max 1 request per 24 hours
        $recent = DataDownloadRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($recent) {
            return GlobalFunction::sendSimpleResponse(false, 'You can only request a data export once every 24 hours');
        }

        $downloadRequest = new DataDownloadRequest();
        $downloadRequest->user_id = $user->id;
        $downloadRequest->status = DataDownloadRequest::STATUS_PENDING;
        $downloadRequest->save();

        // Dispatch the export job
        \App\Jobs\ExportUserDataJob::dispatch($downloadRequest->id, $user->id);

        return GlobalFunction::sendSimpleResponse(true, 'Data export request submitted. You will be notified when it is ready.');
    }

    public function fetchDataDownloadRequests(Request $request)
    {
        $user = $request->authUser;

        $requests = DataDownloadRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'status' => $req->status,
                    'file_size' => $req->file_size,
                    'ready_at' => $req->ready_at?->toISOString(),
                    'expires_at' => $req->expires_at?->toISOString(),
                    'created_at' => $req->created_at?->toISOString(),
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Data download requests fetched', $requests);
    }

    public function downloadMyData(Request $request)
    {
        $user = $request->authUser;

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $downloadRequest = DataDownloadRequest::where('id', $request->request_id)
            ->where('user_id', $user->id)
            ->where('status', DataDownloadRequest::STATUS_READY)
            ->first();

        if (!$downloadRequest) {
            return GlobalFunction::sendSimpleResponse(false, 'Download not available');
        }

        if ($downloadRequest->expires_at && $downloadRequest->expires_at->isPast()) {
            $downloadRequest->status = DataDownloadRequest::STATUS_EXPIRED;
            $downloadRequest->save();
            return GlobalFunction::sendSimpleResponse(false, 'Download link has expired');
        }

        $filePath = storage_path('app/public/exports/' . basename($downloadRequest->file_path));
        if (!file_exists($filePath)) {
            return GlobalFunction::sendSimpleResponse(false, 'File not found');
        }

        return response()->download($filePath, 'your_data.zip');
    }
}
