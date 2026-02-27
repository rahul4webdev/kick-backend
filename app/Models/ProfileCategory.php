<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileCategory extends Model
{
    use HasFactory;
    public $table = "profile_categories";

    protected $fillable = ['name', 'account_type', 'requires_approval', 'is_active', 'sort_order'];

    public function subCategories()
    {
        return $this->hasMany(ProfileSubCategory::class, 'category_id', 'id');
    }
}
