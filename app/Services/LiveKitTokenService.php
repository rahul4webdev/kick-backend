<?php

namespace App\Services;

class LiveKitTokenService
{
    /**
     * Generate a LiveKit access token (HS256 JWT).
     *
     * @param string $apiKey       LiveKit API key (used as JWT issuer)
     * @param string $apiSecret    LiveKit API secret (used for HMAC signing)
     * @param string $roomName     Room name to grant access to
     * @param string $identity     Unique participant identity
     * @param string $name         Display name for the participant
     * @param bool   $canPublish   Whether participant can publish tracks
     * @param bool   $canSubscribe Whether participant can subscribe to tracks
     * @param int    $ttlSeconds   Token validity duration
     * @return string JWT token string
     */
    public static function generateToken(
        string $apiKey,
        string $apiSecret,
        string $roomName,
        string $identity,
        string $name = '',
        bool   $canPublish = true,
        bool   $canSubscribe = true,
        int    $ttlSeconds = 3600
    ): string {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $now = time();
        $payload = self::base64UrlEncode(json_encode([
            'iss'  => $apiKey,
            'sub'  => $identity,
            'name' => $name ?: $identity,
            'nbf'  => $now,
            'exp'  => $now + $ttlSeconds,
            'iat'  => $now,
            'jti'  => $identity . '_' . $now,
            'video' => [
                'roomJoin'       => true,
                'room'           => $roomName,
                'canPublish'     => $canPublish,
                'canSubscribe'   => $canSubscribe,
                'canPublishData' => true,
            ],
        ]));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $apiSecret, true)
        );

        return "$header.$payload.$signature";
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
