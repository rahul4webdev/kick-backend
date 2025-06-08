<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Report extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_report';
	public $primaryKey = 'report_id';
	public $timestamps = true;
	public $incrementing = false;
}
