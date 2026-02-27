<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GlobalSettings extends Model
{
    use HasFactory;
    public $table = "tbl_settings";

    const CACHE_KEY = 'global_settings';
    const CACHE_TTL = 3600; // 60 minutes

    public static function getCached()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::first();
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            Cache::forget(self::CACHE_KEY);
            Cache::forget('api_settings_payload');
        });
    }
}
