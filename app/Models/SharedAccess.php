<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedAccess extends Model
{
    protected $table = 'tbl_shared_access';

    protected $fillable = [
        'account_user_id',
        'member_user_id',
        'role',
        'status',
        'permissions',
        'invited_by',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    // Roles
    const ROLE_ADMIN = 1;
    const ROLE_EDITOR = 2;
    const ROLE_VIEWER = 3;

    // Statuses
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_DECLINED = 2;

    const MAX_TEAM_MEMBERS = 3;

    // Default permissions per role
    public static function defaultPermissions(int $role): array
    {
        return match ($role) {
            self::ROLE_ADMIN => [
                'can_post' => true,
                'can_reply_comments' => true,
                'can_manage_inbox' => true,
                'can_view_analytics' => true,
                'can_manage_products' => true,
                'can_manage_team' => true,
            ],
            self::ROLE_EDITOR => [
                'can_post' => true,
                'can_reply_comments' => true,
                'can_manage_inbox' => false,
                'can_view_analytics' => true,
                'can_manage_products' => false,
                'can_manage_team' => false,
            ],
            self::ROLE_VIEWER => [
                'can_post' => false,
                'can_reply_comments' => false,
                'can_manage_inbox' => false,
                'can_view_analytics' => true,
                'can_manage_products' => false,
                'can_manage_team' => false,
            ],
            default => [],
        };
    }

    public static function roleLabel(int $role): string
    {
        return match ($role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_EDITOR => 'Editor',
            self::ROLE_VIEWER => 'Viewer',
            default => 'Unknown',
        };
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions[$permission] ?? false;
    }

    public function accountOwner()
    {
        return $this->belongsTo(Users::class, 'account_user_id');
    }

    public function member()
    {
        return $this->belongsTo(Users::class, 'member_user_id');
    }

    public function inviter()
    {
        return $this->belongsTo(Users::class, 'invited_by');
    }
}
