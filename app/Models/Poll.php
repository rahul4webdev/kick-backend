<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $table = 'tbl_polls';
    public $timestamps = false;

    public function options()
    {
        return $this->hasMany(PollOption::class, 'poll_id')->orderBy('sort_order');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class, 'poll_id');
    }

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }
}
