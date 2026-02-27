<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    public $table = "tbl_collections";

    protected $fillable = ['user_id', 'name', 'cover_post_id', 'is_default', 'post_count', 'is_shared'];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function coverPost()
    {
        return $this->belongsTo(Posts::class, 'cover_post_id');
    }

    public function saves()
    {
        return $this->hasMany(PostSaves::class, 'collection_id');
    }

    public function members()
    {
        return $this->hasMany(CollectionMember::class, 'collection_id');
    }

    public function acceptedMembers()
    {
        return $this->hasMany(CollectionMember::class, 'collection_id')
            ->where('status', CollectionMember::STATUS_ACCEPTED);
    }
}
