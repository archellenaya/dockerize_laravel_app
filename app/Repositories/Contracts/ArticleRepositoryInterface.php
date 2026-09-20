<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Exceptions\DuplicateArticleException;
use App\Models\Article;
use App\Support\ArticleFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data access boundary for articles. Business logic (validation,
 * deduplication rules, category/source resolution) belongs in a service
 * class, not here - this contract only knows how to read and write
 * article records.
 */
interface ArticleRepositoryInterface
{
    /**
     * Paginate articles newest-first, with their category and source
     * relations eager-loaded for display, narrowed by any filters that
     * are set.
     */
    public function paginateLatest(int $perPage, ArticleFilters $filters): LengthAwarePaginator;

    /**
     * All articles matching the filters, newest-first, capped at
     * $limit - meant for a bulk export, not paginated display, so there
     * is no page number, just a hard ceiling to keep memory bounded.
     *
     * @return Collection<int, Article>
     */
    public function allMatching(ArticleFilters $filters, int $limit): Collection;

    /**
     * Whether an article with this exact URL already exists.
     */
    public function existsByUrl(string $url): bool;

    /**
     * Persist a new article.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws DuplicateArticleException if an article with the same URL
     *                                   was inserted concurrently after the caller's own existence
     *                                   check.
     */
    public function create(array $attributes): Article;

    /**
     * Eager-load the relations a detail/listing view needs.
     */
    public function loadRelations(Article $article): Article;
}
