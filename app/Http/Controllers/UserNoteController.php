<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\UserNote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserNoteController extends Controller
{
    /**
     * Create or update the current user's note.
     * Params: content (string, max 60), emoji (optional string)
     */
    public function createNote(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:60',
            'emoji' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Delete any existing active note
        UserNote::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->delete();

        $note = UserNote::create([
            'user_id' => $user->id,
            'content' => $request->content,
            'emoji' => $request->emoji,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        return GlobalFunction::sendDataResponse(true, 'Note created', [
            'id' => $note->id,
            'content' => $note->content,
            'emoji' => $note->emoji,
            'expires_at' => $note->expires_at->toISOString(),
            'created_at' => $note->created_at->toISOString(),
        ]);
    }

    /**
     * Fetch the current user's active note.
     */
    public function fetchMyNote(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $note = UserNote::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$note) {
            return GlobalFunction::sendDataResponse(true, 'No active note', null);
        }

        return GlobalFunction::sendDataResponse(true, 'Note fetched', [
            'id' => $note->id,
            'content' => $note->content,
            'emoji' => $note->emoji,
            'expires_at' => $note->expires_at->toISOString(),
            'created_at' => $note->created_at->toISOString(),
        ]);
    }

    /**
     * Fetch active notes from users the current user follows.
     */
    public function fetchFollowerNotes(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        // Get IDs of users the current user follows
        $followingIds = \DB::table('tbl_followers')
            ->where('from_user_id', $user->id)
            ->pluck('to_user_id')
            ->toArray();

        // Include current user's note
        $followingIds[] = $user->id;

        $notes = UserNote::whereIn('user_id', $followingIds)
            ->where('expires_at', '>', Carbon::now())
            ->with(['user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'user_id' => $note->user_id,
                    'content' => $note->content,
                    'emoji' => $note->emoji,
                    'expires_at' => $note->expires_at->toISOString(),
                    'created_at' => $note->created_at->toISOString(),
                    'user' => $note->user,
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Notes fetched', $notes);
    }

    /**
     * Delete the current user's active note.
     */
    public function deleteNote(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        UserNote::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Note deleted');
    }
}
