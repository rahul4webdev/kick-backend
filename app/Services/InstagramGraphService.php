<?php

namespace App\Services;

use App\Models\GlobalSettings;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class InstagramGraphService
{
    private static function getClient(): Client
    {
        return new Client(['timeout' => 30, 'http_errors' => false]);
    }

    /**
     * Exchange authorization code for a short-lived access token.
     */
    public static function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $settings = GlobalSettings::getCached();
        $client = self::getClient();

        $response = $client->post('https://api.instagram.com/oauth/access_token', [
            'form_params' => [
                'client_id'     => $settings->instagram_app_id,
                'client_secret' => $settings->instagram_app_secret,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200 || !isset($body['access_token'])) {
            Log::error('Instagram token exchange failed', ['response' => $body]);
            throw new \Exception($body['error_message'] ?? 'Failed to exchange code for token');
        }

        return $body; // ['access_token' => '...', 'user_id' => ...]
    }

    /**
     * Exchange short-lived token for a long-lived token (60 days).
     */
    public static function exchangeForLongLivedToken(string $shortToken): array
    {
        $settings = GlobalSettings::getCached();
        $client = self::getClient();

        $response = $client->get('https://graph.instagram.com/access_token', [
            'query' => [
                'grant_type'    => 'ig_exchange_token',
                'client_secret' => $settings->instagram_app_secret,
                'access_token'  => $shortToken,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200 || !isset($body['access_token'])) {
            Log::error('Instagram long-lived token exchange failed', ['response' => $body]);
            throw new \Exception($body['error_message'] ?? 'Failed to get long-lived token');
        }

        return $body; // ['access_token' => '...', 'token_type' => 'bearer', 'expires_in' => 5184000]
    }

    /**
     * Refresh a long-lived token before it expires.
     */
    public static function refreshLongLivedToken(string $token): array
    {
        $client = self::getClient();

        $response = $client->get('https://graph.instagram.com/refresh_access_token', [
            'query' => [
                'grant_type'   => 'ig_refresh_token',
                'access_token' => $token,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200 || !isset($body['access_token'])) {
            Log::error('Instagram token refresh failed', ['response' => $body]);
            throw new \Exception($body['error_message'] ?? 'Failed to refresh token');
        }

        return $body;
    }

    /**
     * Get Instagram user profile (id, username, account_type).
     */
    public static function getUserProfile(string $accessToken): ?array
    {
        $client = self::getClient();

        $response = $client->get('https://graph.instagram.com/v21.0/me', [
            'query' => [
                'fields'       => 'id,username,account_type',
                'access_token' => $accessToken,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200 || !isset($body['id'])) {
            Log::error('Instagram profile fetch failed', ['response' => $body]);
            return null;
        }

        return $body;
    }

    /**
     * Get user's media (videos/reels) with cursor-based pagination.
     */
    public static function getUserMedia(string $accessToken, ?string $after = null, int $limit = 50): array
    {
        $client = self::getClient();

        $query = [
            'fields'       => 'id,media_type,media_url,thumbnail_url,caption,timestamp,permalink',
            'limit'        => $limit,
            'access_token' => $accessToken,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        $response = $client->get('https://graph.instagram.com/v21.0/me/media', [
            'query' => $query,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200) {
            Log::error('Instagram media fetch failed', ['response' => $body]);
            return ['data' => [], 'paging' => null];
        }

        return $body;
    }

    /**
     * Download a video from Instagram CDN to a temp file.
     * Returns the temp file path.
     */
    public static function downloadVideo(string $mediaUrl): string
    {
        $client = new Client(['timeout' => 120, 'http_errors' => false]);
        $tempPath = tempnam(sys_get_temp_dir(), 'ig_video_') . '.mp4';

        $response = $client->get($mediaUrl, ['sink' => $tempPath]);

        if ($response->getStatusCode() !== 200) {
            @unlink($tempPath);
            throw new \Exception('Failed to download Instagram video (HTTP ' . $response->getStatusCode() . ')');
        }

        return $tempPath;
    }

    /**
     * Download a thumbnail from Instagram CDN to a temp file.
     */
    public static function downloadThumbnail(string $thumbnailUrl): string
    {
        $client = new Client(['timeout' => 30, 'http_errors' => false]);
        $tempPath = tempnam(sys_get_temp_dir(), 'ig_thumb_') . '.jpg';

        $response = $client->get($thumbnailUrl, ['sink' => $tempPath]);

        if ($response->getStatusCode() !== 200) {
            @unlink($tempPath);
            throw new \Exception('Failed to download Instagram thumbnail');
        }

        return $tempPath;
    }

    /**
     * Check if a token is expired (with 1-day safety buffer).
     */
    public static function isTokenExpired(?string $expiresAt): bool
    {
        if (!$expiresAt) return true;
        return Carbon::parse($expiresAt)->subDay()->isPast();
    }

    /**
     * Check if a token is expiring soon (within 7 days).
     */
    public static function isTokenExpiringSoon(?string $expiresAt): bool
    {
        if (!$expiresAt) return true;
        return Carbon::parse($expiresAt)->subDays(7)->isPast();
    }
}
