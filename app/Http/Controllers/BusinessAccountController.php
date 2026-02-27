<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\ProfileCategory;
use App\Models\ProfileSubCategory;
use App\Models\Users;
use App\Models\VerificationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class BusinessAccountController extends Controller
{
    public function fetchProfileCategories(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'account_type' => 'required|integer|in:1,2,3,4',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $at = $request->account_type;
        $categories = Cache::remember("profile_categories:{$at}", 1800, function () use ($at) {
            return ProfileCategory::where('account_type', $at)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return GlobalFunction::sendDataResponse(true, 'categories fetched successfully', $categories);
    }

    public function fetchProfileSubCategories(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'category_id' => 'required|exists:profile_categories,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $cid = $request->category_id;
        $subCategories = Cache::remember("profile_subcategories:{$cid}", 1800, function () use ($cid) {
            return ProfileSubCategory::where('category_id', $cid)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return GlobalFunction::sendDataResponse(true, 'sub categories fetched successfully', $subCategories);
    }

    public function convertToBusinessAccount(Request $request)
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
            'account_type' => 'required|integer|in:1,2,3,4',
            'profile_category_id' => 'required|exists:profile_categories,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Validate that category belongs to the specified account type
        $category = ProfileCategory::find($request->profile_category_id);
        if ($category->account_type != $request->account_type) {
            return GlobalFunction::sendSimpleResponse(false, 'category does not match account type!');
        }

        $user->account_type = $request->account_type;
        $user->profile_category_id = $request->profile_category_id;

        if ($request->has('profile_sub_category_id')) {
            $user->profile_sub_category_id = $request->profile_sub_category_id;
        }

        // If category requires approval, set to pending; otherwise auto-approve
        if ($category->requires_approval) {
            $user->business_status = Constants::businessStatusPending;
        } else {
            $user->business_status = Constants::businessStatusApproved;
        }

        $user->save();

        $user = GlobalFunction::prepareUserFullData($user->id);
        return GlobalFunction::sendDataResponse(true, 'account converted successfully', $user);
    }

    public function fetchMyBusinessStatus(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $data = [
            'account_type' => $user->account_type,
            'business_status' => $user->business_status,
            'is_monetized' => $user->is_monetized,
            'monetization_status' => $user->monetization_status,
            'profile_category' => $user->profileCategory,
            'profile_sub_category' => $user->profileSubCategory,
            'verification_documents' => $user->verificationDocuments,
        ];

        return GlobalFunction::sendDataResponse(true, 'business status fetched successfully', $data);
    }

    public function revertToPersonalAccount(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $user->account_type = Constants::accountTypePersonal;
        $user->business_status = Constants::businessStatusNotApplied;
        $user->profile_category_id = null;
        $user->profile_sub_category_id = null;
        $user->is_monetized = false;
        $user->monetization_status = Constants::businessStatusNotApplied;
        $user->save();

        $user = GlobalFunction::prepareUserFullData($user->id);
        return GlobalFunction::sendDataResponse(true, 'reverted to personal account', $user);
    }

    // Admin: approve/reject business account
    public function updateBusinessStatus(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
            'business_status' => 'required|integer|in:2,3', // 2=approved, 3=rejected
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::find($request->user_id);
        $user->business_status = $request->business_status;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'business status updated successfully');
    }
}
