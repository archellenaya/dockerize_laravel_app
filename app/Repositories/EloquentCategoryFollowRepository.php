<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CategoryFollow;
use App\Models\User;
use App\Repositories\Contracts\CategoryFollowRepositoryInterface;
use Illuminate\Support\Collection;

final class EloquentCategoryFollowRepository implements CategoryFollowRepositoryInterface
{
    public function isFollowing(int $userId, int $categoryId): bool
    {
        return CategoryFollow::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->exists();
    }

    public function follow(int $userId, int $categoryId): void
    {
        CategoryFollow::firstOrCreate([
            'user_id' => $userId,
            'category_id' => $categoryId,
        ]);
    }

    public function unfollow(int $userId, int $categoryId): void
    {
        CategoryFollow::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->delete();
    }

    public function followersOf(int $categoryId): Collection
    {
        return User::query()
            ->whereHas('categoryFollows', fn ($query) => $query->where('category_id', $categoryId))
            ->get();
    }

    public function followedCategoryIds(int $userId, array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        return CategoryFollow::where('user_id', $userId)
            ->whereIn('category_id', $categoryIds)
            ->pluck('category_id')
            ->all();
    }
}
