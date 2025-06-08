<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Sound extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_sound';
	public $primaryKey = 'sound_id';
	public $timestamps = true;
	public $incrementing = false;
}
