<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    protected $table = 'tbl_search_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'keyword',
        'search_type',
        'result_count',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
