<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Notification extends Authenticatable
{
	// protected $connection = '/tenant';
	protected $table = 'tbl_notification';
	public $primaryKey = 'notification_id';

	public function received_user()
	{
		return $this->hasOne(User::class, 'user_id', 'received_user_id');
	}
	public function sender_user()
	{
		return $this->hasOne(User::class, 'user_id', 'sender_user_id');
	}
}
