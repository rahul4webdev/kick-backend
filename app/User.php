<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'tbl_users';
    protected $primaryKey  = 'user_id';
    public $timestamps = true;

    public static function get_random_string($field_code='user_id')
	{
        $random_unique  =  sprintf('%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

        $user = User::where('user_id', '=', $random_unique)->first();
        if ($user != null) {
            User::get_random_string();
        }
        return $random_unique;
    }
}
