<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Estimates reading time from plain text, using the commonly cited
 * average adult silent-reading speed of ~200 words per minute.
 */
final class ReadingTime
{
    private const WORDS_PER_MINUTE = 200;

    /**
     * @return int Whole minutes, rounded up, minimum 1 so an article
     *             with any content never claims to be a "0 min read".
     */
    public static function estimateMinutes(?string $text): int
    {
        if ($text === null || trim($text) === '') {
            return 1;
        }

        $wordCount = count(array_filter(preg_split('/\s+/u', trim($text)) ?: []));

        return max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE));
    }
}
