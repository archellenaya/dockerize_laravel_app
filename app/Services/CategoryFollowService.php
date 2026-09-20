<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Repositories\Contracts\CategoryFollowRepositoryInterface;
use App\Services\Contracts\CategoryFollowServiceInterface;
use Illuminate\Support\Collection;

final class CategoryFollowService implements CategoryFollowServiceInterface
{
    public function __construct(
        private readonly CategoryFollowRepositoryInterface $follows,
    ) {}

    public function follow(int $userId, Category $category): void
    {
        $this->follows->follow($userId, $category->id);
    }

    public function unfollow(int $userId, Category $category): void
    {
        $this->follows->unfollow($userId, $category->id);
    }

    public function isFollowing(int $userId, Category $category): bool
    {
        return $this->follows->isFollowing($userId, $category->id);
    }

    public function followedIds(int $userId, iterable $categories): array
    {
        $categoryIds = [];

        foreach ($categories as $category) {
            $categoryIds[] = $category->id;
        }

        return $this->follows->followedCategoryIds($userId, $categoryIds);
    }

    /**
     * @return Collection<int, User>
     */
    public function followersOf(Category $category): Collection
    {
        return $this->follows->followersOf($category->id);
    }
}
