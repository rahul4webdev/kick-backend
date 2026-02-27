<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMilestone extends Model
{
    protected $table = 'user_milestones';

    protected $fillable = [
        'user_id',
        'type',
        'data_id',
        'metadata',
        'is_seen',
        'is_shared',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_seen' => 'boolean',
        'is_shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * All milestone type definitions.
     */
    public static function milestoneTypes(): array
    {
        return [
            'followers_100' => ['label' => '100 Followers', 'threshold' => 100, 'icon' => 'star'],
            'followers_1k' => ['label' => '1K Followers', 'threshold' => 1000, 'icon' => 'star'],
            'followers_10k' => ['label' => '10K Followers', 'threshold' => 10000, 'icon' => 'fire'],
            'followers_100k' => ['label' => '100K Followers', 'threshold' => 100000, 'icon' => 'fire'],
            'followers_1m' => ['label' => '1M Followers', 'threshold' => 1000000, 'icon' => 'trophy'],
            'viral_post' => ['label' => 'First Viral Post', 'threshold' => null, 'icon' => 'rocket'],
            'anniversary_1y' => ['label' => '1 Year on Platform', 'threshold' => null, 'icon' => 'cake'],
            'first_post' => ['label' => 'First Post', 'threshold' => null, 'icon' => 'pencil'],
            'posts_100' => ['label' => '100 Posts', 'threshold' => 100, 'icon' => 'grid'],
        ];
    }
}
