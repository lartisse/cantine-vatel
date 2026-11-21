<?php

namespace App\Services;

use App\Models\Review;
use App\Support\Settings;

/** Détection des mots interdits dans les avis. */
class ModerationService
{
    /** @return string[] mots interdits trouvés */
    public function findForbidden(?string $text): array
    {
        if (! $text) {
            return [];
        }
        $lower = mb_strtolower($text);
        $found = [];
        foreach (Settings::forbiddenWords() as $word) {
            $w = mb_strtolower(trim($word));
            if ($w === '') {
                continue;
            }
            $pattern = preg_match('/^[\p{L}]+$/u', $w) ? '/(?<![\p{L}])'.preg_quote($w, '/').'(?![\p{L}])/u' : '/'.preg_quote($w, '/').'/u';
            if (preg_match($pattern, $lower)) {
                $found[] = $word;
            }
        }

        return array_values(array_unique($found));
    }

    /** Signale (et masque jusqu'à décision) un avis contenant un mot interdit, sauf s'il a déjà été approuvé. */
    public function apply(Review $review): Review
    {
        if ($review->approved) {
            return $review;
        }
        $words = $this->findForbidden($review->comment);
        if ($words) {
            $review->status = 'flagged';
            $review->flag_words = $words;
            $review->flag_reason = 'Mot interdit';
        }

        return $review;
    }

    public function highlight(?string $text, ?array $words): string
    {
        $html = e((string) $text);
        foreach ($words ?? [] as $w) {
            $html = preg_replace('/('.preg_quote(e($w), '/').')/iu', '<mark class="bad">$1</mark>', $html);
        }

        return $html;
    }
}
