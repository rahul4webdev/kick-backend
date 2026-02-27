<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationLog extends Model
{
    protected $table = 'tbl_moderation_logs';

    protected $fillable = [
        'moderator_id', 'action', 'target_type',
        'target_id', 'notes',
    ];

    public function moderator()
    {
        return $this->belongsTo(Users::class, 'moderator_id');
    }

    public static function log(int $moderatorId, string $action, string $targetType, int $targetId, ?string $notes = null): self
    {
        return self::create([
            'moderator_id' => $moderatorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'notes' => $notes,
        ]);
    }
}
