<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    protected $table = 'tbl_coin_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'coins',
        'direction',
        'related_user_id',
        'reference_id',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function relatedUser()
    {
        return $this->belongsTo(Users::class, 'related_user_id');
    }
}
