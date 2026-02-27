<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateLink extends Model
{
    protected $table = 'tbl_affiliate_links';

    protected $fillable = [
        'creator_id',
        'product_id',
        'affiliate_code',
        'commission_rate',
        'click_count',
        'purchase_count',
        'total_earnings',
        'status',
    ];

    protected $casts = [
        'commission_rate' => 'float',
    ];

    const STATUS_ACTIVE = 1;
    const STATUS_PAUSED = 2;

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function earnings()
    {
        return $this->hasMany(AffiliateEarning::class, 'affiliate_link_id');
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } while (self::where('affiliate_code', $code)->exists());
        return $code;
    }
}
