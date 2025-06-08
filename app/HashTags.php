<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class HashTags extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_hash_tags';
	public $primaryKey = 'hash_tag_id';
	public $timestamps = true;
	public $incrementing = false;
}
