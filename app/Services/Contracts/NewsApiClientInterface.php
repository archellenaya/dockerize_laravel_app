<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Exceptions\NewsApiException;

/**
 * Dedicated API communication boundary for NewsAPI. Nothing outside this
 * contract should know that NewsAPI - specifically - is the data source.
 */
interface NewsApiClientInterface
{
    /**
     * Fetch top headlines for one or more sources/categories.
     *
     * @param  array<int, string>  $sources
     * @param  array<int, string>  $categories
     * @return array<int, array<string, mixed>>
     *
     * @throws NewsApiException if the client is not configured with an
     *                          API key. Per-request failures (rate limits, timeouts) are
     *                          not thrown - see getLastErrorMessage().
     */
    public function fetch(array $sources, array $categories, ?string $from = null, ?string $to = null, int $limit = 10): array;

    /**
     * The most recent per-request failure message, if fetch() returned
     * fewer articles than requested (or none) because some or all of the
     * underlying HTTP requests failed.
     */
    public function getLastErrorMessage(): ?string;
}
