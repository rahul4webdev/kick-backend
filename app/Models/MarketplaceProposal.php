<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProposal extends Model
{
    protected $table = 'tbl_marketplace_proposals';

    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_DECLINED = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;

    const INITIATED_BY_BRAND = 1;
    const INITIATED_BY_CREATOR = 2;

    public function campaign()
    {
        return $this->belongsTo(MarketplaceCampaign::class, 'campaign_id');
    }

    public function brand()
    {
        return $this->belongsTo(Users::class, 'brand_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class, 'creator_user_id');
    }

    public function deliverablePost()
    {
        return $this->belongsTo(Posts::class, 'deliverable_post_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }
}
