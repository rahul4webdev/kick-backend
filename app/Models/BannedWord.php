<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BannedWord extends Model
{
    protected $table = 'tbl_banned_words';

    protected $fillable = [
        'word', 'severity', 'is_active', 'category',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getActiveWords(): array
    {
        return Cache::remember('banned_words', 300, function () {
            return self::where('is_active', true)
                ->orderBy('severity', 'DESC')
                ->get()
                ->toArray();
        });
    }

    public static function checkText(string $text): array
    {
        $words = self::getActiveWords();
        $violations = [];
        $lowerText = strtolower($text);

        foreach ($words as $word) {
            if (str_contains($lowerText, strtolower($word['word']))) {
                $violations[] = $word;
            }
        }

        return $violations;
    }
}
