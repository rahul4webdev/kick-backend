<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\LivestreamReplay;
use Illuminate\Http\Request;

class LivestreamReplayController extends Controller
{
    public function saveReplay(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $replay = LivestreamReplay::create([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
            'title' => $request->title,
            'thumbnail' => $request->thumbnail,
            'recording_url' => $request->recording_url,
            'duration_seconds' => $request->duration_seconds ?? 0,
            'peak_viewers' => $request->peak_viewers ?? 0,
            'total_likes' => $request->total_likes ?? 0,
            'total_gifts_coins' => $request->total_gifts_coins ?? 0,
        ]);

        return [
            'status' => true,
            'message' => 'Replay saved successfully',
            'data' => $replay,
        ];
    }

    public function fetchMyReplays(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $replays = LivestreamReplay::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        return [
            'status' => true,
            'message' => 'Replays fetched',
            'data' => $replays,
        ];
    }

    public function fetchUserReplays(Request $request)
    {
        $replays = LivestreamReplay::where('user_id', $request->user_id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        return [
            'status' => true,
            'message' => 'Replays fetched',
            'data' => $replays,
        ];
    }

    public function deleteReplay(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        LivestreamReplay::where('id', $request->replay_id)
            ->where('user_id', $user->id)
            ->update(['is_active' => false]);

        return GlobalFunction::sendSimpleResponse(true, 'Replay deleted');
    }

    public function updateReplay(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $replay = LivestreamReplay::where('id', $request->replay_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$replay) {
            return GlobalFunction::sendSimpleResponse(false, 'Replay not found');
        }

        if ($request->has('title')) $replay->title = $request->title;
        if ($request->has('thumbnail')) $replay->thumbnail = $request->thumbnail;
        $replay->save();

        return GlobalFunction::sendSimpleResponse(true, 'Replay updated');
    }

    public function incrementViewCount(Request $request)
    {
        LivestreamReplay::where('id', $request->replay_id)
            ->where('is_active', true)
            ->increment('view_count');

        return GlobalFunction::sendSimpleResponse(true, 'View counted');
    }
}
