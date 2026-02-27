<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentLog extends Model
{
    public $table = "tbl_consent_logs";

    protected $fillable = [
        'user_id', 'consent_type', 'version', 'action', 'ip_address', 'user_agent',
    ];

    public static function recordConsent(int $userId, string $type, string $version, string $action, ?string $ip = null, ?string $ua = null): void
    {
        self::create([
            'user_id' => $userId,
            'consent_type' => $type,
            'version' => $version,
            'action' => $action,
            'ip_address' => $ip,
            'user_agent' => $ua,
        ]);
    }
}
