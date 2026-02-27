<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileSubCategory extends Model
{
    use HasFactory;
    public $table = "profile_sub_categories";

    protected $fillable = ['category_id', 'name', 'is_active', 'sort_order'];

    public function category()
    {
        return $this->belongsTo(ProfileCategory::class, 'category_id', 'id');
    }
}
