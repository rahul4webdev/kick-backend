<?php

namespace App\Http\Controllers;

use App\Jobs\ImportInstagramVideoJob;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\InstagramImports;
use App\Services\InstagramGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InstagramController extends Controller
{
    /**
     * Handle OAuth callback — exchange code for tokens and save connection.
     */
    public function handleOAuthCallback(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        try {
            $settings = GlobalSettings::getCached();
            $redirectUri = $settings->instagram_redirect_uri;

            // Step 1: Exchange code for short-lived token
            $shortTokenData = InstagramGraphService::exchangeCodeForToken($request->code, $redirectUri);
            $shortToken = $shortTokenData['access_token'];
            $igUserId = (string) $shortTokenData['user_id'];

            // Step 2: Exchange for long-lived token (60 days)
            $longTokenData = InstagramGraphService::exchangeForLongLivedToken($shortToken);
            $longToken = $longTokenData['access_token'];
            $expiresIn = $longTokenData['expires_in'] ?? 5184000;

            // Step 3: Verify account type (Business or Creator only)
            $profile = InstagramGraphService::getUserProfile($longToken);
            if (!$profile) {
                return GlobalFunction::sendSimpleResponse(false, 'Failed to fetch Instagram profile');
            }

            $accountType = $profile['account_type'] ?? '';
            if (!in_array($accountType, ['BUSINESS', 'MEDIA_CREATOR'])) {
                return GlobalFunction::sendSimpleResponse(false,
                    'Only Business or Creator Instagram accounts are supported. Your account type: ' . $accountType);
            }

            // Step 4: Save connection to user
            $user->instagram_user_id = $igUserId;
            $user->instagram_access_token = $longToken;
            $user->instagram_token_expires_at = now()->addSeconds($expiresIn);
            $user->save();

            return GlobalFunction::sendDataResponse(true, 'Instagram connected successfully', [
                'instagram_user_id' => $igUserId,
                'username' => $profile['username'] ?? null,
                'account_type' => $accountType,
                'token_expires_at' => $user->instagram_token_expires_at->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram OAuth failed', ['error' => $e->getMessage()]);
            return GlobalFunction::sendSimpleResponse(false, 'Instagram connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Instagram account.
     */
    public function disconnect(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $user->instagram_user_id = null;
        $user->instagram_access_token = null;
        $user->instagram_token_expires_at = null;
        $user->instagram_auto_sync = false;
        $user->instagram_last_sync_at = null;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'Instagram disconnected');
    }

    /**
     * Get current connection status.
     */
    public function getConnectionStatus(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $isConnected = !empty($user->instagram_user_id);
        $data = [
            'is_connected' => $isConnected,
            'instagram_user_id' => $user->instagram_user_id,
            'auto_sync_enabled' => (bool) $user->instagram_auto_sync,
            'token_expires_at' => $user->instagram_token_expires_at,
            'last_sync_at' => $user->instagram_last_sync_at,
            'token_expired' => $isConnected ? InstagramGraphService::isTokenExpired($user->instagram_token_expires_at) : false,
        ];

        return GlobalFunction::sendDataResponse(true, 'Connection status', $data);
    }

    /**
     * Fetch user's Instagram videos/reels (proxied through backend).
     */
    public function fetchMedia(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        if (empty($user->instagram_user_id)) {
            return GlobalFunction::sendSimpleResponse(false, 'Instagram not connected');
        }

        if (InstagramGraphService::isTokenExpired($user->instagram_token_expires_at)) {
            return GlobalFunction::sendSimpleResponse(false, 'Instagram token expired. Please reconnect.');
        }

        try {
            $result = InstagramGraphService::getUserMedia(
                $user->instagram_access_token,
                $request->after
            );

            // Filter for videos only
            $videos = array_filter($result['data'] ?? [], function ($item) {
                return in_array($item['media_type'] ?? '', ['VIDEO', 'REELS']);
            });

            // Get already-imported media IDs
            $mediaIds = array_column($videos, 'id');
            $importedIds = InstagramImports::where('user_id', $user->id)
                ->whereIn('instagram_media_id', $mediaIds)
                ->pluck('instagram_media_id')
                ->toArray();

            // Add is_imported flag
            $videos = array_map(function ($item) use ($importedIds) {
                $item['is_imported'] = in_array($item['id'], $importedIds);
                return $item;
            }, array_values($videos));

            $nextCursor = $result['paging']['cursors']['after'] ?? null;

            return GlobalFunction::sendDataResponse(true, 'Instagram media fetched', [
                'media' => $videos,
                'next_cursor' => $nextCursor,
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram fetchMedia failed', ['error' => $e->getMessage()]);
            return GlobalFunction::sendSimpleResponse(false, 'Failed to fetch Instagram media');
        }
    }

    /**
     * Import a single Instagram video.
     */
    public function importVideo(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $validator = Validator::make($request->all(), [
            'instagram_media_id' => 'required|string',
            'media_data' => 'required|string', // JSON
        ]);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $mediaData = json_decode($request->media_data, true);
        if (!$mediaData || !isset($mediaData['media_url'])) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid media data');
        }

        // Check duplicate
        $exists = InstagramImports::where('user_id', $user->id)
            ->where('instagram_media_id', $request->instagram_media_id)
            ->exists();
        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'This video has already been imported');
        }

        // Dispatch import job
        ImportInstagramVideoJob::dispatch(
            $user->id,
            $request->instagram_media_id,
            $mediaData,
            $mediaData['caption'] ?? null
        );

        return GlobalFunction::sendSimpleResponse(true, 'Video queued for import');
    }

    /**
     * Bulk import multiple Instagram videos.
     */
    public function importBulk(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $validator = Validator::make($request->all(), [
            'media_list' => 'required|string', // JSON array
        ]);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $mediaList = json_decode($request->media_list, true);
        if (!is_array($mediaList) || empty($mediaList)) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid media list');
        }

        $queued = 0;
        $skipped = 0;

        foreach ($mediaList as $media) {
            $mediaId = $media['id'] ?? null;
            $mediaUrl = $media['media_url'] ?? null;
            if (!$mediaId || !$mediaUrl) {
                $skipped++;
                continue;
            }

            // Check duplicate
            $exists = InstagramImports::where('user_id', $user->id)
                ->where('instagram_media_id', $mediaId)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            // Check daily limit
            $canPost = GlobalFunction::checkIfUserCanPost($user);
            if (!$canPost['status']) {
                break;
            }

            ImportInstagramVideoJob::dispatch(
                $user->id,
                $mediaId,
                $media,
                $media['caption'] ?? null
            );
            $queued++;
        }

        return GlobalFunction::sendDataResponse(true, "$queued video(s) queued for import", [
            'queued' => $queued,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Toggle auto-sync on/off.
     */
    public function toggleAutoSync(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        if (empty($user->instagram_user_id)) {
            return GlobalFunction::sendSimpleResponse(false, 'Instagram not connected');
        }

        $user->instagram_auto_sync = $request->enabled == 1 || $request->enabled === true;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true,
            $user->instagram_auto_sync ? 'Auto-sync enabled' : 'Auto-sync disabled');
    }

    /**
     * Get import history for the current user.
     */
    public function getImportHistory(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $imports = InstagramImports::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return GlobalFunction::sendDataResponse(true, 'Import history', $imports);
    }
}
