<?php

namespace App\Helpers;

use App\Models\Users;
use Illuminate\Support\Facades\Redis;

class AnalyticsHelper
{
    public static function publishEvent(string $type, ?int $userId, array $data = []): void
    {
        try {
            $user = $userId ? Users::find($userId) : null;
            $event = array_merge([
                'type' => $type,
                'userId' => $userId,
                'country' => $user->countryCode ?? null,
                'region' => $user->regionName ?? null,
                'device' => $user->device ?? null,
                'deviceBrand' => $user->device_brand ?? null,
                'deviceModel' => $user->device_model ?? null,
                'deviceOs' => $user->device_os ?? null,
                'deviceOsVersion' => $user->device_os_version ?? null,
                'timestamp' => now()->timestamp,
            ], $data);

            Redis::publish('kick:analytics', json_encode($event));
        } catch (\Throwable $e) {
            // Analytics is non-critical, silently ignore failures
        }
    }
}
