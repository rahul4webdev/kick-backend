<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserViolation extends Model
{
    protected $table = 'tbl_user_violations';

    protected $fillable = [
        'user_id', 'moderator_id', 'severity', 'reason',
        'description', 'reference_post_id', 'reference_report_id',
        'action_taken', 'ban_days',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function moderator()
    {
        return $this->belongsTo(Users::class, 'moderator_id');
    }
}
