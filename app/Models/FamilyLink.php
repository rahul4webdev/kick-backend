<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyLink extends Model
{
    protected $table = 'tbl_family_links';

    protected $fillable = [
        'parent_user_id',
        'teen_user_id',
        'pairing_code',
        'status',
        'controls',
    ];

    protected $casts = [
        'controls' => 'array',
    ];

    // Statuses
    const STATUS_PENDING = 0;
    const STATUS_LINKED = 1;
    const STATUS_UNLINKED = 2;

    // Default parental controls
    public static function defaultControls(): array
    {
        return [
            'daily_screen_time_min' => 60,      // 60 minutes default
            'dm_restricted' => false,            // can receive DMs
            'live_restricted' => false,          // can view live streams
            'discover_restricted' => false,      // can browse discover
            'purchase_restricted' => true,       // cannot make purchases
            'live_stream_restricted' => true,    // cannot go live
            'activity_reports' => true,          // parent receives activity reports
        ];
    }

    public static function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_LINKED => 'Linked',
            self::STATUS_UNLINKED => 'Unlinked',
            default => 'Unknown',
        };
    }

    public function parent()
    {
        return $this->belongsTo(Users::class, 'parent_user_id');
    }

    public function teen()
    {
        return $this->belongsTo(Users::class, 'teen_user_id');
    }
}
