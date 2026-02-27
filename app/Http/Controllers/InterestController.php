<?php

namespace App\Http\Controllers;

use App\Models\FeedKeywordFilter;
use App\Models\FeedPreference;
use App\Models\GlobalFunction;
use App\Models\Interest;
use App\Models\NotInterested;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class InterestController extends Controller
{
    // App API: fetch all active interests
    public function fetchInterests(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $interests = Cache::remember('active_interests', 3600, function () {
            return Interest::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        return GlobalFunction::sendDataResponse(true, 'interests fetched successfully', $interests);
    }

    // App API: update user's selected interests
    public function updateMyInterests(Request $request)
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
            'interest_ids' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user->interest_ids = $request->interest_ids;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'interests updated successfully');
    }

    // App API: fetch user's feed preferences
    public function fetchFeedPreferences(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $preferences = FeedPreference::where('user_id', $user->id)->get()
            ->keyBy('interest_id')
            ->map(fn($p) => $p->weight)
            ->toArray();

        return GlobalFunction::sendDataResponse(true, 'Feed preferences fetched', $preferences);
    }

    // App API: update a single feed preference
    public function updateFeedPreference(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'interest_id' => 'required|integer|exists:interests,id',
            'weight' => 'required|integer|in:-1,0,1',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        FeedPreference::updateOrCreate(
            ['user_id' => $user->id, 'interest_id' => $request->interest_id],
            ['weight' => $request->weight]
        );

        // Clear feed preference cache
        Cache::forget("feed_prefs:{$user->id}");

        return GlobalFunction::sendSimpleResponse(true, 'Feed preference updated');
    }

    public function resetFeed(Request $request)
    {
        $user = request()->authUser;

        // Clear not-interested posts
        NotInterested::where('user_id', $user->id)->delete();
        Cache::forget("not_interested_ids:{$user->id}");

        // Clear feed preferences (topic weights)
        FeedPreference::where('user_id', $user->id)->delete();
        Cache::forget("feed_prefs:{$user->id}");

        return GlobalFunction::sendSimpleResponse(true, 'Feed has been reset successfully');
    }

    public function fetchMyKeywordFilters(Request $request)
    {
        $user = request()->authUser;
        $keywords = FeedKeywordFilter::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'keyword', 'created_at']);

        return GlobalFunction::sendDataResponse(true, 'Keywords fetched', $keywords);
    }

    public function addKeywordFilter(Request $request)
    {
        $user = request()->authUser;

        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $keyword = strtolower(trim($request->keyword));

        // Check limit (max 200 keywords)
        $count = FeedKeywordFilter::where('user_id', $user->id)->count();
        if ($count >= 200) {
            return GlobalFunction::sendSimpleResponse(false, 'Maximum 200 keywords allowed');
        }

        // Check duplicate
        $exists = FeedKeywordFilter::where('user_id', $user->id)->where('keyword', $keyword)->exists();
        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'Keyword already added');
        }

        $filter = new FeedKeywordFilter();
        $filter->user_id = $user->id;
        $filter->keyword = $keyword;
        $filter->save();

        Cache::forget("keyword_filters:{$user->id}");

        return GlobalFunction::sendSimpleResponse(true, 'Keyword filter added');
    }

    public function removeKeywordFilter(Request $request)
    {
        $user = request()->authUser;

        $validator = Validator::make($request->all(), [
            'keyword_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        FeedKeywordFilter::where('id', $request->keyword_id)
            ->where('user_id', $user->id)
            ->delete();

        Cache::forget("keyword_filters:{$user->id}");

        return GlobalFunction::sendSimpleResponse(true, 'Keyword filter removed');
    }

    // Admin: list all interests (with pagination)
    public function listInterests(Request $request)
    {
        $interests = Interest::orderBy('sort_order')->get();
        return view('interests', ['interests' => $interests]);
    }

    // Admin: add interest
    public function addInterest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $interest = new Interest();
        $interest->name = $request->name;
        if ($request->has('icon')) {
            $interest->icon = GlobalFunction::saveFileAndGivePath($request->icon);
        }
        $interest->sort_order = $request->sort_order ?? 0;
        $interest->save();
        Cache::forget('active_interests');

        return GlobalFunction::sendSimpleResponse(true, 'interest added successfully');
    }

    // Admin: update interest
    public function updateInterest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:interests,id',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $interest = Interest::find($request->id);
        if ($request->has('name')) $interest->name = $request->name;
        if ($request->has('is_active')) $interest->is_active = $request->is_active;
        if ($request->has('sort_order')) $interest->sort_order = $request->sort_order;
        if ($request->has('icon')) {
            $interest->icon = GlobalFunction::saveFileAndGivePath($request->icon);
        }
        $interest->save();
        Cache::forget('active_interests');

        return GlobalFunction::sendSimpleResponse(true, 'interest updated successfully');
    }

    // Admin: delete interest
    public function deleteInterest(Request $request)
    {
        $interest = Interest::find($request->id);
        if (!$interest) {
            return GlobalFunction::sendSimpleResponse(false, 'interest not found');
        }
        $interest->delete();
        Cache::forget('active_interests');

        return GlobalFunction::sendSimpleResponse(true, 'interest deleted successfully');
    }
}
