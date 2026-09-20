<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Data access boundary for a user's saved (bookmarked) articles.
 */
interface BookmarkRepositoryInterface
{
    public function existsForUser(int $userId, int $articleId): bool;

    public function add(int $userId, int $articleId): void;

    public function remove(int $userId, int $articleId): void;

    /**
     * Paginate the articles a user has saved, newest bookmark first, with
     * category/source relations eager-loaded for display.
     */
    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;

    /**
     * Given a set of article IDs, return the subset the user has saved -
     * one query, used to annotate a listing without an N+1 lookup per
     * card.
     *
     * @param  array<int, int>  $articleIds
     * @return array<int, int>
     */
    public function articleIdsBookmarkedByUser(int $userId, array $articleIds): array;
}
