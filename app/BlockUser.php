<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class BlockUser extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_user_block';
	public $primaryKey = 'id';
	public $timestamps = true;
	public $incrementing = false;
}
