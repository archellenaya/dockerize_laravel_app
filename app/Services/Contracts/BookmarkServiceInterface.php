<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Business logic for saving/unsaving articles to a user's personal
 * dashboard.
 */
interface BookmarkServiceInterface
{
    public function save(int $userId, Article $article): void;

    public function unsave(int $userId, Article $article): void;

    public function isSaved(int $userId, Article $article): bool;

    public function listSavedForUser(int $userId, int $perPage = 12): LengthAwarePaginator;

    /**
     * Which of the given articles the user has saved - one query, meant
     * to annotate a listing page without a per-card lookup.
     *
     * @param  iterable<Article>  $articles
     * @return array<int, int>
     */
    public function filterSavedIds(int $userId, iterable $articles): array;
}
