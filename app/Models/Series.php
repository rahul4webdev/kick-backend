<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    use HasFactory;

    protected $table = 'tbl_series';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'genre',
        'language',
        'episode_count',
        'total_views',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function episodes()
    {
        return $this->hasMany(Posts::class, 'id', 'id')
            ->whereRaw("content_type = 4 AND (content_metadata->>'series_id')::int = ?", [$this->id]);
    }
}
