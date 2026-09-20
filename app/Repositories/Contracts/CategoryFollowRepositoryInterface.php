<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Data access boundary for which users follow which categories.
 */
interface CategoryFollowRepositoryInterface
{
    public function isFollowing(int $userId, int $categoryId): bool;

    public function follow(int $userId, int $categoryId): void;

    public function unfollow(int $userId, int $categoryId): void;

    /**
     * The users following a category, for sending notifications.
     *
     * @return Collection<int, User>
     */
    public function followersOf(int $categoryId): Collection;

    /**
     * Given a set of category IDs, return the subset the user follows -
     * one query, meant to annotate a listing without a per-row lookup.
     *
     * @param  array<int, int>  $categoryIds
     * @return array<int, int>
     */
    public function followedCategoryIds(int $userId, array $categoryIds): array;
}
