<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class GlobalSettings extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_settings';
	public $incrementing = false;
}
