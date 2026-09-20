<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Repositories\Contracts\BookmarkRepositoryInterface;
use App\Services\Contracts\BookmarkServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BookmarkService implements BookmarkServiceInterface
{
    public function __construct(
        private readonly BookmarkRepositoryInterface $bookmarks,
    ) {}

    public function save(int $userId, Article $article): void
    {
        $this->bookmarks->add($userId, $article->id);
    }

    public function unsave(int $userId, Article $article): void
    {
        $this->bookmarks->remove($userId, $article->id);
    }

    public function isSaved(int $userId, Article $article): bool
    {
        return $this->bookmarks->existsForUser($userId, $article->id);
    }

    public function listSavedForUser(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->bookmarks->paginateForUser($userId, $perPage);
    }

    public function filterSavedIds(int $userId, iterable $articles): array
    {
        $articleIds = [];

        foreach ($articles as $article) {
            $articleIds[] = $article->id;
        }

        return $this->bookmarks->articleIdsBookmarkedByUser($userId, $articleIds);
    }
}
