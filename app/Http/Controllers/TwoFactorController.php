<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\TwoFactorAuth;
use App\Models\TwoFaToken;
use App\Models\UserAuthTokens;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Setup 2FA: Generate secret + QR URI + backup codes (not yet confirmed)
     * Requires auth token (user is logged in)
     */
    public function setup2FA(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if ($user->two_fa_enabled) {
            return GlobalFunction::sendSimpleResponse(false, '2FA is already enabled');
        }

        $secret = TwoFactorAuth::generateSecret();
        $backupCodes = TwoFactorAuth::generateBackupCodes();

        // Store secret temporarily (not yet confirmed)
        $user->two_fa_secret = Crypt::encryptString($secret);
        $user->two_fa_backup_codes = json_encode(array_map(
            fn($code) => ['code' => $code, 'used' => false],
            $backupCodes
        ));
        $user->save();

        $otpAuthUri = TwoFactorAuth::getOtpAuthUri(
            $secret,
            $user->identity,
            'Kick'
        );

        return [
            'status' => true,
            'message' => 'Scan the QR code with your authenticator app',
            'data' => [
                'secret' => $secret,
                'otp_auth_uri' => $otpAuthUri,
                'backup_codes' => $backupCodes,
            ],
        ];
    }

    /**
     * Confirm 2FA setup: Verify user can generate correct code before enabling
     */
    public function confirm2FA(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if ($user->two_fa_enabled) {
            return GlobalFunction::sendSimpleResponse(false, '2FA is already enabled');
        }

        if (!$user->two_fa_secret) {
            return GlobalFunction::sendSimpleResponse(false, 'Please set up 2FA first');
        }

        $code = $request->code;
        if (!$code || strlen($code) !== 6) {
            return GlobalFunction::sendSimpleResponse(false, 'Please enter a valid 6-digit code');
        }

        $secret = Crypt::decryptString($user->two_fa_secret);
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid code. Please try again');
        }

        $user->two_fa_enabled = true;
        $user->two_fa_verified_at = now();
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, '2FA has been enabled successfully');
    }

    /**
     * Disable 2FA
     */
    public function disable2FA(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if (!$user->two_fa_enabled) {
            return GlobalFunction::sendSimpleResponse(false, '2FA is not enabled');
        }

        // Require current TOTP code to disable
        $code = $request->code;
        if (!$code || strlen($code) !== 6) {
            return GlobalFunction::sendSimpleResponse(false, 'Please enter your current 2FA code to disable');
        }

        $secret = Crypt::decryptString($user->two_fa_secret);
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid code');
        }

        $user->two_fa_enabled = false;
        $user->two_fa_secret = null;
        $user->two_fa_backup_codes = null;
        $user->two_fa_verified_at = null;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, '2FA has been disabled');
    }

    /**
     * Verify TOTP code during login (uses temp token, not auth token)
     */
    public function verifyTOTP(Request $request)
    {
        $tempToken = $request->temp_token;
        $code = $request->code;

        if (!$tempToken || !$code) {
            return GlobalFunction::sendSimpleResponse(false, 'Token and code are required');
        }

        // Find valid temp token
        $tokenRecord = TwoFaToken::where('token', $tempToken)
            ->valid()
            ->first();

        if (!$tokenRecord) {
            return GlobalFunction::sendSimpleResponse(false, 'Session expired. Please log in again');
        }

        $user = Users::find($tokenRecord->user_id);
        if (!$user || !$user->two_fa_enabled) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid session');
        }

        $secret = Crypt::decryptString($user->two_fa_secret);
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid verification code');
        }

        // Delete temp token
        TwoFaToken::where('user_id', $user->id)->delete();

        // Generate real auth token
        $token = GlobalFunction::generateUserAuthToken($user);

        $user = GlobalFunction::prepareUserFullData($user->id);
        $user->new_register = false;
        $user->token = $token;
        $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

        return GlobalFunction::sendDataResponse(true, 'Verification successful', $user);
    }

    /**
     * Verify backup code during login (uses temp token)
     */
    public function verifyBackupCode(Request $request)
    {
        $tempToken = $request->temp_token;
        $backupCode = $request->backup_code;

        if (!$tempToken || !$backupCode) {
            return GlobalFunction::sendSimpleResponse(false, 'Token and backup code are required');
        }

        $tokenRecord = TwoFaToken::where('token', $tempToken)
            ->valid()
            ->first();

        if (!$tokenRecord) {
            return GlobalFunction::sendSimpleResponse(false, 'Session expired. Please log in again');
        }

        $user = Users::find($tokenRecord->user_id);
        if (!$user || !$user->two_fa_enabled) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid session');
        }

        // Check backup codes
        $codes = json_decode($user->two_fa_backup_codes, true) ?? [];
        $backupCode = strtoupper(trim($backupCode));
        $found = false;

        foreach ($codes as &$entry) {
            if ($entry['code'] === $backupCode && !$entry['used']) {
                $entry['used'] = true;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid or already used backup code');
        }

        // Save updated backup codes
        $user->two_fa_backup_codes = json_encode($codes);
        $user->save();

        // Delete temp token
        TwoFaToken::where('user_id', $user->id)->delete();

        // Generate real auth token
        $token = GlobalFunction::generateUserAuthToken($user);

        $user = GlobalFunction::prepareUserFullData($user->id);
        $user->new_register = false;
        $user->token = $token;
        $user->following_ids = GlobalFunction::fetchUserFollowingIds($user->id);

        return GlobalFunction::sendDataResponse(true, 'Verification successful', $user);
    }

    /**
     * Regenerate backup codes (requires auth + current TOTP)
     */
    public function regenerateBackupCodes(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if (!$user->two_fa_enabled) {
            return GlobalFunction::sendSimpleResponse(false, '2FA is not enabled');
        }

        $code = $request->code;
        if (!$code || strlen($code) !== 6) {
            return GlobalFunction::sendSimpleResponse(false, 'Please enter your current 2FA code');
        }

        $secret = Crypt::decryptString($user->two_fa_secret);
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid code');
        }

        $backupCodes = TwoFactorAuth::generateBackupCodes();
        $user->two_fa_backup_codes = json_encode(array_map(
            fn($code) => ['code' => $code, 'used' => false],
            $backupCodes
        ));
        $user->save();

        return [
            'status' => true,
            'message' => 'New backup codes generated',
            'data' => ['backup_codes' => $backupCodes],
        ];
    }

    /**
     * Get 2FA status for logged-in user
     */
    public function get2FAStatus(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $backupCodesRemaining = 0;
        if ($user->two_fa_backup_codes) {
            $codes = json_decode($user->two_fa_backup_codes, true) ?? [];
            $backupCodesRemaining = count(array_filter($codes, fn($c) => !$c['used']));
        }

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'two_fa_enabled' => (bool) $user->two_fa_enabled,
                'verified_at' => $user->two_fa_verified_at,
                'backup_codes_remaining' => $backupCodesRemaining,
            ],
        ];
    }

    /**
     * Helper: Generate a temporary 2FA token for a user
     */
    public static function generateTempToken(int $userId): string
    {
        // Clean old tokens for this user
        TwoFaToken::where('user_id', $userId)->delete();

        $token = Str::random(64);
        TwoFaToken::create([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => now()->addMinutes(5),
        ]);

        return $token;
    }
}
