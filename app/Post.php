<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class Post extends Authenticatable
{
	use HasApiTokens;
	protected $table = 'tbl_post';
	public $primaryKey = 'post_id';
	public $timestamps = true;
	public $incrementing = false;

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

}
