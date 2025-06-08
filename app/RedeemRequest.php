<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class RedeemRequest extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_redeem_request';
	public $primaryKey = 'redeem_request_id';
	public $timestamps = true;
	public $incrementing = false;
}
