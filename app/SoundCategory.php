<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class SoundCategory extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_sound_category';
	public $primaryKey = 'sound_category_id';
	public $timestamps = true;
	public $incrementing = false;
}
