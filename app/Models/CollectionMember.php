<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionMember extends Model
{
    use HasFactory;

    public $table = "tbl_collection_members";

    protected $fillable = ['collection_id', 'user_id', 'role', 'status', 'invited_by'];

    // Roles
    const ROLE_MEMBER = 1;
    const ROLE_ADMIN = 2;

    // Statuses
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_DECLINED = 2;

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function inviter()
    {
        return $this->belongsTo(Users::class, 'invited_by');
    }
}
