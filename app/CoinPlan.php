<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class CoinPlan extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_coin_plan';
	public $primaryKey = 'coin_plan_id';
	public $timestamps = true;
	public $incrementing = false;
}
