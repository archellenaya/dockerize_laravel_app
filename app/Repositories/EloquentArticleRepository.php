<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\DuplicateArticleException;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Support\ArticleFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

final class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function paginateLatest(int $perPage, ArticleFilters $filters): LengthAwarePaginator
    {
        return $this->filtered($filters)->paginate($perPage);
    }

    public function allMatching(ArticleFilters $filters, int $limit): Collection
    {
        return $this->filtered($filters)->limit($limit)->get();
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

    /**
     * The filtering logic shared by every "give me articles matching
     * these criteria, newest first" read path.
     */
    private function filtered(ArticleFilters $filters): Builder
    {
        return Article::query()
            ->with(['category', 'source'])
            ->when($filters->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters->categorySlug, function (Builder $query, string $slug) {
                $query->whereHas('category', fn (Builder $query) => $query->where('slug', $slug));
            })
            ->when($filters->sourceSlug, function (Builder $query, string $slug) {
                $query->whereHas('source', fn (Builder $query) => $query->where('slug', $slug));
            })
            ->when($filters->from, fn (Builder $query, string $from) => $query->whereDate('published_at', '>=', $from))
            ->when($filters->to, fn (Builder $query, string $to) => $query->whereDate('published_at', '<=', $to))
            ->latest('published_at');
    }
}
