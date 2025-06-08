<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class ProfileCategory extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_profile_category';
	public $primaryKey = 'profile_category_id';
	public $timestamps = true;
	public $incrementing = false;
}
