<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    use HasFactory;
    public $table = "tbl_users";

    public function links()
    {
        return $this->hasMany(UserLinks::class, 'user_id', 'id');
    }
    public function stories()
    {
        return $this->hasMany(Story::class, 'user_id', 'id');
    }

    public function profileCategory()
    {
        return $this->belongsTo(ProfileCategory::class, 'profile_category_id', 'id');
    }

    public function profileSubCategory()
    {
        return $this->belongsTo(ProfileSubCategory::class, 'profile_sub_category_id', 'id');
    }

    public function verificationDocuments()
    {
        return $this->hasMany(VerificationDocument::class, 'user_id', 'id');
    }

    public function pendingFollowRequests()
    {
        return $this->hasMany(FollowRequest::class, 'to_user_id', 'id')
            ->where('status', Constants::followRequestPending);
    }
}
