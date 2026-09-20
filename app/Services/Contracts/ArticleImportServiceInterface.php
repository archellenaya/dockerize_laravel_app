<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface ArticleImportServiceInterface
{
    /**
     * Sanitize, validate, and persist a batch of raw articles (as returned
     * by NewsApiClientInterface::fetch), skipping duplicates and invalid
     * records rather than failing the whole batch.
     *
     * @param  array<int, array<string, mixed>>  $rawArticles
     * @return array{
     *     created: int,
     *     duplicates: int,
     *     invalid: int,
     *     errors: array<int, array{url: mixed, errors: array<int, string>}>,
     * }
     */
    public function importMany(array $rawArticles): array;
}
