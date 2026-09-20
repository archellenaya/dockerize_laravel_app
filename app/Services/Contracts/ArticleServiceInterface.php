<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Business logic for reading articles, as consumed by the web UI.
 */
interface ArticleServiceInterface
{
    public function listLatest(int $perPage = 12): LengthAwarePaginator;

    public function loadDetails(Article $article): Article;
}
