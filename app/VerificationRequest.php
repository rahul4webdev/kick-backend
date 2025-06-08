<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class VerificationRequest extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_verification_request';
	public $primaryKey = 'verification_request_id';
	public $timestamps = true;
	public $incrementing = false;
}
