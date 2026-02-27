<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    protected $table = 'tbl_poll_votes';
    public $timestamps = false;

    public function option()
    {
        return $this->belongsTo(PollOption::class, 'option_id');
    }
}
