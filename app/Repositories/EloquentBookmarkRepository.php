<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Article;
use App\Models\ArticleBookmark;
use App\Repositories\Contracts\BookmarkRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentBookmarkRepository implements BookmarkRepositoryInterface
{
    public function existsForUser(int $userId, int $articleId): bool
    {
        return ArticleBookmark::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->exists();
    }

    public function add(int $userId, int $articleId): void
    {
        ArticleBookmark::firstOrCreate([
            'user_id' => $userId,
            'article_id' => $articleId,
        ]);
    }

    public function remove(int $userId, int $articleId): void
    {
        ArticleBookmark::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->delete();
    }

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Article::query()
            ->join('article_bookmarks', 'article_bookmarks.article_id', '=', 'articles.id')
            ->where('article_bookmarks.user_id', $userId)
            ->with(['category', 'source'])
            ->orderByDesc('article_bookmarks.created_at')
            ->select('articles.*')
            ->paginate($perPage);
    }

    public function articleIdsBookmarkedByUser(int $userId, array $articleIds): array
    {
        if ($articleIds === []) {
            return [];
        }

        return ArticleBookmark::where('user_id', $userId)
            ->whereIn('article_id', $articleIds)
            ->pluck('article_id')
            ->all();
    }
}
