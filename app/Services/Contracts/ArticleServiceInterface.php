<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Support\ArticleFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Business logic for reading articles, as consumed by the web UI.
 */
interface ArticleServiceInterface
{
    public function listLatest(int $perPage = 12, ?ArticleFilters $filters = null): LengthAwarePaginator;

    public function loadDetails(Article $article): Article;

    /**
     * Categories usable as a filter option - i.e. that have at least one
     * article.
     *
     * @return Collection<int, Category>
     */
    public function availableCategories(): Collection;

    /**
     * Sources usable as a filter option - i.e. that have at least one
     * article.
     *
     * @return Collection<int, Source>
     */
    public function availableSources(): Collection;
}
