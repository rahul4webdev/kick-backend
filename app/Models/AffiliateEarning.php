<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateEarning extends Model
{
    protected $table = 'tbl_affiliate_earnings';

    public $timestamps = false;

    protected $fillable = [
        'affiliate_link_id',
        'order_id',
        'commission_coins',
        'status',
        'created_at',
    ];

    public function affiliateLink()
    {
        return $this->belongsTo(AffiliateLink::class, 'affiliate_link_id');
    }

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }
}
