<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\DuplicateArticleException;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function paginateLatest(int $perPage): LengthAwarePaginator
    {
        return Article::with(['category', 'source'])
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function existsByUrl(string $url): bool
    {
        return Article::where('url', $url)->exists();
    }

    public function create(array $attributes): Article
    {
        try {
            return Article::create($attributes);
        } catch (UniqueConstraintViolationException $exception) {
            throw new DuplicateArticleException(
                sprintf('An article with the url [%s] already exists.', $attributes['url'] ?? ''),
                previous: $exception,
            );
        }
    }

    public function loadRelations(Article $article): Article
    {
        return $article->load(['category', 'source']);
    }
}
