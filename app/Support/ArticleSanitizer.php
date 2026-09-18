<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

/**
 * Normalizes a raw article array (as returned by NewsApiClient::fetch) into
 * clean, storable values ahead of validation.
 */
class ArticleSanitizer
{
    /**
     * Query parameters that carry no editorial meaning and only fragment
     * otherwise-identical article URLs (breaking duplicate detection).
     *
     * @var array<int, string>
     */
    protected const TRACKING_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'fbclid', 'gclid', 'ref', 'ref_src', 'cmp', 'ito', 'ns_campaign', 'ns_mchannel',
    ];

    /**
     * @param  array<string, mixed>  $article
     * @return array<string, mixed>
     */
    public static function sanitize(array $article): array
    {
        return [
            'title' => static::text($article['title'] ?? null, 255),
            'description' => static::text($article['description'] ?? null),
            'content' => static::text($article['content'] ?? null),
            'author' => static::text($article['author'] ?? null, 255),
            'url' => static::url($article['url'] ?? null),
            'image_url' => static::url($article['url_to_image'] ?? null),
            'published_at' => static::date($article['published_at'] ?? null),
            'source_name' => static::text($article['source'] ?? null, 255) ?: 'Unknown',
            'category_name' => static::text($article['category'] ?? null, 255),
        ];
    }

    protected static function text(?string $value, ?int $maxLength = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        // NewsAPI truncates `content` with a "... [+1234 chars]" marker.
        $value = preg_replace('/\s*\[\+\d+\s+chars]$/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $maxLength ? Str::limit($value, $maxLength, '') : $value;
    }

    protected static function url(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $parts = parse_url($value);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $path = rtrim($parts['path'] ?? '', '/');
        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $parsedQuery);

            foreach ($parsedQuery as $key => $val) {
                if (in_array(strtolower((string) $key), static::TRACKING_PARAMS, true)) {
                    continue;
                }

                $query[$key] = $val;
            }

            ksort($query);
        }

        $normalized = "{$scheme}://{$host}{$path}";

        if (! empty($query)) {
            $normalized .= '?'.http_build_query($query);
        }

        return $normalized;
    }

    protected static function date(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->utc()->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
