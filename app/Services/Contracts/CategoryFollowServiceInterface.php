<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Business logic for following categories to receive email notifications
 * when a new article is imported into them.
 */
interface CategoryFollowServiceInterface
{
    public function follow(int $userId, Category $category): void;

    public function unfollow(int $userId, Category $category): void;

    public function isFollowing(int $userId, Category $category): bool;

    /**
     * Which of the given categories the user follows - one query, meant
     * to annotate a listing without a per-row lookup.
     *
     * @param  iterable<Category>  $categories
     * @return array<int, int>
     */
    public function followedIds(int $userId, iterable $categories): array;

    /**
     * The users to notify when a new article lands in this category.
     *
     * @return Collection<int, User>
     */
    public function followersOf(Category $category): Collection;
}
