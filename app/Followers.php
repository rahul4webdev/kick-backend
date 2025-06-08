<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Followers extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_followers';
	public $primaryKey = 'follower_id';
	public $timestamps = true;
	public $incrementing = false;
}
