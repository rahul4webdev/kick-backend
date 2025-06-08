<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Like extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_likes';
	public $primaryKey = 'like_id';
	public $timestamps = true;
	public $incrementing = false;
}
