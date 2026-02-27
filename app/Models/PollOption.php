<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    protected $table = 'tbl_poll_options';
    public $timestamps = false;

    public function poll()
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }
}
