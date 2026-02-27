<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataDownloadRequest extends Model
{
    protected $table = 'tbl_data_download_requests';
    protected $guarded = [];

    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_READY = 2;
    const STATUS_EXPIRED = 3;
    const STATUS_FAILED = 4;

    protected $casts = [
        'ready_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
