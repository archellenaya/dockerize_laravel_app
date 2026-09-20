<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SourceRepositoryInterface;
use App\Services\Contracts\ArticleServiceInterface;
use App\Support\ArticleFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Business logic for reading and filtering articles. This is where
 * read-side rules (e.g. "only show published articles", personalization,
 * caching) belong as the application grows - the controller/Livewire
 * component should never need to know that.
 */
final class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly CategoryRepositoryInterface $categories,
        private readonly SourceRepositoryInterface $sources,
    ) {}

    public function listLatest(int $perPage = 12, ?ArticleFilters $filters = null): LengthAwarePaginator
    {
        return $this->articles->paginateLatest($perPage, $filters ?? new ArticleFilters);
    }

    public function loadDetails(Article $article): Article
    {
        return $this->articles->loadRelations($article);
    }

    /**
     * @return Collection<int, Category>
     */
    public function availableCategories(): Collection
    {
        return $this->categories->allWithArticles();
    }

    /**
     * @return Collection<int, Source>
     */
    public function availableSources(): Collection
    {
        return $this->sources->allWithArticles();
    }

    public function exportable(ArticleFilters $filters, int $limit = 1000): Collection
    {
        return $this->articles->allMatching($filters, $limit);
    }
}
