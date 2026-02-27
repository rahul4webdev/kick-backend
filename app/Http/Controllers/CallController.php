<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\CallParticipant;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Users;
use App\Services\LiveKitTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CallController extends Controller
{
    /**
     * Initiate a call — creates call record and sends push to participants.
     */
    public function initiateCall(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'call_type' => 'required|in:1,2',       // 1=voice, 2=video
            'participant_ids' => 'required|array|min:1|max:7',
            'participant_ids.*' => 'integer',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $participantIds = $request->participant_ids;
        $isGroup = count($participantIds) > 1;
        $roomId = 'call_' . $user->id . '_' . time();

        // Create call record
        $call = Call::create([
            'room_id' => $roomId,
            'caller_id' => $user->id,
            'call_type' => $request->call_type,
            'is_group' => $isGroup,
            'status' => 0, // ringing
        ]);

        // Add participants
        foreach ($participantIds as $pid) {
            CallParticipant::create([
                'call_id' => $call->id,
                'user_id' => $pid,
                'status' => 0, // ringing
            ]);
        }

        // Send push notification to each participant
        foreach ($participantIds as $pid) {
            $participant = Users::find($pid);
            if (!$participant) continue;

            $notificationData = [
                'type' => 'call',
                'notification_data' => json_encode([
                    'call_id' => $call->id,
                    'room_id' => $roomId,
                    'caller_id' => $user->id,
                    'caller_name' => $user->fullname,
                    'caller_username' => $user->username,
                    'caller_profile' => $user->profile_photo,
                    'call_type' => (int) $request->call_type,
                    'is_group' => $isGroup,
                ]),
            ];

            $callTypeLabel = $request->call_type == 2 ? 'Video' : 'Voice';
            $title = $user->fullname ?? $user->username;
            $body = "Incoming {$callTypeLabel} Call";

            $payload = GlobalFunction::generatePushNotificationPayload(
                $participant->device_type,
                'token',
                $participant->device_token,
                $title,
                $body,
                null,
                $notificationData
            );

            GlobalFunction::sendPushNotification($payload);
        }

        return GlobalFunction::sendDataResponse(true, 'Call initiated', [
            'call_id' => $call->id,
            'room_id' => $roomId,
        ]);
    }

    /**
     * Answer a call — update status to answered.
     */
    public function answerCall(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = ['call_id' => 'required|integer'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $call = Call::find($request->call_id);
        if (!$call) {
            return response()->json(['status' => false, 'message' => 'Call not found']);
        }

        // Update call status to answered
        if ($call->status == 0) {
            $call->update(['status' => 1, 'started_at' => now()]);
        }

        // Update participant status
        CallParticipant::where('call_id', $call->id)
            ->where('user_id', $user->id)
            ->update(['status' => 1, 'joined_at' => now()]);

        return GlobalFunction::sendSimpleResponse(true, 'Call answered');
    }

    /**
     * End/leave a call.
     */
    public function endCall(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = ['call_id' => 'required|integer'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $call = Call::find($request->call_id);
        if (!$call) {
            return response()->json(['status' => false, 'message' => 'Call not found']);
        }

        // Update participant
        CallParticipant::where('call_id', $call->id)
            ->where('user_id', $user->id)
            ->update(['status' => 2, 'left_at' => now()]);

        // Check if all participants have left
        $activeParticipants = CallParticipant::where('call_id', $call->id)
            ->where('status', 1)
            ->count();

        // If caller is ending or no active participants left, end the call
        if ($user->id == $call->caller_id || $activeParticipants == 0) {
            $duration = 0;
            if ($call->started_at) {
                $duration = now()->diffInSeconds($call->started_at);
            }
            $call->update([
                'status' => 2,
                'ended_at' => now(),
                'duration_sec' => $duration,
            ]);

            // Mark all ringing participants as missed
            CallParticipant::where('call_id', $call->id)
                ->where('status', 0)
                ->update(['status' => 3]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Call ended');
    }

    /**
     * Reject a call.
     */
    public function rejectCall(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = ['call_id' => 'required|integer'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $call = Call::find($request->call_id);
        if (!$call) {
            return response()->json(['status' => false, 'message' => 'Call not found']);
        }

        CallParticipant::where('call_id', $call->id)
            ->where('user_id', $user->id)
            ->update(['status' => 4]);

        // If 1-on-1 call and only participant rejected, end call
        if (!$call->is_group) {
            $call->update(['status' => 4, 'ended_at' => now()]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Call rejected');
    }

    /**
     * Generate a LiveKit access token for the authenticated user.
     */
    public function generateLiveKitToken(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'room_name'   => 'required|string|max:200',
            'can_publish' => 'sometimes|boolean',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $settings = GlobalSettings::first();
        $host      = $settings->livekit_host ?? '';
        $apiKey    = $settings->livekit_api_key ?? '';
        $apiSecret = $settings->livekit_api_secret ?? '';

        if (!$host || !$apiKey || !$apiSecret) {
            return response()->json(['status' => false, 'message' => 'LiveKit is not configured.']);
        }

        $canPublish = $request->input('can_publish', true);

        $jwt = LiveKitTokenService::generateToken(
            $apiKey,
            $apiSecret,
            $request->room_name,
            (string) $user->id,
            $user->username ?? '',
            (bool) $canPublish,
            true,
            3600
        );

        return GlobalFunction::sendDataResponse(true, 'Token generated', [
            'token'  => $jwt,
            'ws_url' => $host,
        ]);
    }

    /**
     * Fetch call history for the authenticated user.
     */
    public function fetchCallHistory(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $limit = $request->input('limit', 20);

        $callIds = CallParticipant::where('user_id', $user->id)
            ->pluck('call_id')
            ->toArray();

        // Also include calls where user is the caller
        $callerCallIds = Call::where('caller_id', $user->id)->pluck('id')->toArray();
        $allCallIds = array_unique(array_merge($callIds, $callerCallIds));

        $query = Call::whereIn('id', $allCallIds)
            ->with(['caller:id,username,fullname,profile_photo,is_verify', 'participants.user:id,username,fullname,profile_photo,is_verify'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $calls = $query->get();

        return GlobalFunction::sendDataResponse(true, 'Call history fetched', $calls);
    }
}
