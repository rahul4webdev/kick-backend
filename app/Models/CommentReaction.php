<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    protected $table = 'tbl_comment_reactions';

    protected $fillable = [
        'user_id', 'comment_id', 'emoji',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function comment()
    {
        return $this->belongsTo(PostComments::class, 'comment_id');
    }

    /**
     * Get aggregated reaction counts for a comment.
     */
    public static function getReactionCounts(int $commentId): array
    {
        return self::where('comment_id', $commentId)
            ->selectRaw("emoji, COUNT(*) as count")
            ->groupBy('emoji')
            ->get()
            ->pluck('count', 'emoji')
            ->toArray();
    }
}
