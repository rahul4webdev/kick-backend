<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\ScheduledLive;
use App\Models\ScheduledLiveReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduledLiveController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function createScheduledLive(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = [
            'title' => 'required|string|max:255',
            'scheduled_at' => 'required|date|after:now',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $scheduled = new ScheduledLive();
        $scheduled->user_id = $user->id;
        $scheduled->title = $request->title;
        $scheduled->description = $request->description;
        $scheduled->scheduled_at = $request->scheduled_at;
        $scheduled->status = 1; // upcoming

        if ($request->hasFile('cover_image')) {
            $scheduled->cover_image = GlobalFunction::uploadFile($request->file('cover_image'), 'scheduled_lives');
        }

        $scheduled->save();

        return GlobalFunction::sendDataResponse(true, 'Scheduled live created', $scheduled);
    }

    public function fetchScheduledLives(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = ['limit' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $query = ScheduledLive::where('status', 1)
            ->where('scheduled_at', '>', now())
            ->with('user:id,username,fullname,profile_photo,is_verify')
            ->orderBy('scheduled_at', 'ASC')
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '>', $request->last_item_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $lives = $query->get();

        // Add is_reminded flag for current user
        $remindedIds = ScheduledLiveReminder::where('user_id', $user->id)
            ->whereIn('scheduled_live_id', $lives->pluck('id'))
            ->pluck('scheduled_live_id')
            ->toArray();

        $lives->each(function ($live) use ($remindedIds) {
            $live->is_reminded = in_array($live->id, $remindedIds);
        });

        return GlobalFunction::sendDataResponse(true, 'Scheduled lives fetched', $lives);
    }

    public function fetchMyScheduledLives(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $lives = ScheduledLive::where('user_id', $user->id)
            ->where('status', '!=', 4) // exclude cancelled
            ->orderBy('scheduled_at', 'ASC')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'My scheduled lives', $lives);
    }

    public function toggleReminder(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = ['scheduled_live_id' => 'required|exists:tbl_scheduled_lives,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $existing = ScheduledLiveReminder::where('scheduled_live_id', $request->scheduled_live_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            ScheduledLive::where('id', $request->scheduled_live_id)->decrement('reminder_count');
            return GlobalFunction::sendSimpleResponse(true, 'Reminder removed');
        } else {
            ScheduledLiveReminder::create([
                'scheduled_live_id' => $request->scheduled_live_id,
                'user_id' => $user->id,
            ]);
            ScheduledLive::where('id', $request->scheduled_live_id)->increment('reminder_count');
            return GlobalFunction::sendSimpleResponse(true, 'Reminder set');
        }
    }

    public function cancelScheduledLive(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = ['scheduled_live_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $live = ScheduledLive::where('id', $request->scheduled_live_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$live) {
            return GlobalFunction::sendSimpleResponse(false, 'Scheduled live not found');
        }

        $live->status = 4; // cancelled
        $live->save();

        return GlobalFunction::sendSimpleResponse(true, 'Scheduled live cancelled');
    }

    public function updateScheduledLive(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = ['scheduled_live_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $live = ScheduledLive::where('id', $request->scheduled_live_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$live) {
            return GlobalFunction::sendSimpleResponse(false, 'Scheduled live not found');
        }

        if ($request->has('title')) $live->title = $request->title;
        if ($request->has('description')) $live->description = $request->description;
        if ($request->has('scheduled_at')) $live->scheduled_at = $request->scheduled_at;

        if ($request->hasFile('cover_image')) {
            $live->cover_image = GlobalFunction::uploadFile($request->file('cover_image'), 'scheduled_lives');
        }

        $live->save();

        return GlobalFunction::sendDataResponse(true, 'Scheduled live updated', $live);
    }
}
