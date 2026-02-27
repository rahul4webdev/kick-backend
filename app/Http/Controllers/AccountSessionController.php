<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountSessionController extends Controller
{
    /**
     * Store or update account session after login.
     * Called by login endpoints to persist multi-account sessions.
     */
    public static function storeSession(int $userId, string $authToken, ?string $deviceId): void
    {
        if (!$deviceId) return;

        DB::table('tbl_account_sessions')->updateOrInsert(
            ['device_id' => $deviceId, 'user_id' => $userId],
            [
                'auth_token' => $authToken,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Fetch all accounts stored on this device.
     */
    public function fetchDeviceAccounts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['device_id' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $sessions = DB::table('tbl_account_sessions')
            ->where('device_id', $request->device_id)
            ->where('is_active', true)
            ->orderBy('last_used_at', 'DESC')
            ->get();

        $accounts = [];
        foreach ($sessions as $session) {
            $sessionUser = Users::select(explode(',', Constants::userPublicFields))
                ->find($session->user_id);
            if ($sessionUser) {
                $accounts[] = [
                    'user' => $sessionUser,
                    'is_current' => $session->user_id == $user->id,
                    'last_used_at' => $session->last_used_at,
                ];
            }
        }

        return GlobalFunction::sendDataResponse(true, 'device accounts fetched', $accounts);
    }

    /**
     * Switch to a different account on this device.
     * Returns the auth token for the target account.
     */
    public function switchAccount(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'device_id' => 'required',
            'target_user_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $session = DB::table('tbl_account_sessions')
            ->where('device_id', $request->device_id)
            ->where('user_id', $request->target_user_id)
            ->where('is_active', true)
            ->first();

        if (!$session) {
            return GlobalFunction::sendSimpleResponse(false, 'Account session not found');
        }

        // Update last_used_at for the target account
        DB::table('tbl_account_sessions')
            ->where('id', $session->id)
            ->update(['last_used_at' => now()]);

        // Fetch the target user's full data
        $targetUser = Users::find($session->user_id);
        if (!$targetUser || $targetUser->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'Account is not available');
        }

        $data = [
            'auth_token' => $session->auth_token,
            'user' => $targetUser,
        ];

        return GlobalFunction::sendDataResponse(true, 'switched account', $data);
    }

    /**
     * Remove an account from this device.
     */
    public function removeAccountFromDevice(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'device_id' => 'required',
            'target_user_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        // Cannot remove self (use logout for that)
        if ($request->target_user_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Use logout to remove current account');
        }

        DB::table('tbl_account_sessions')
            ->where('device_id', $request->device_id)
            ->where('user_id', $request->target_user_id)
            ->delete();

        return GlobalFunction::sendSimpleResponse(true, 'account removed from device');
    }
}
