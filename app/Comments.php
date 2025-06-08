<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Comments extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_comments';
	public $primaryKey = 'comments_id';
	public $timestamps = true;
	public $incrementing = false;

}
