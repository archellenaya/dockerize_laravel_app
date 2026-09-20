<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\Contracts\ArticleServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Business logic for reading articles. Currently a thin pass-through to
 * the repository, but this is where read-side rules (e.g. "only show
 * published articles", personalization, caching) belong as the
 * application grows - the controller should never need to know that.
 */
final class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
    ) {}

    public function listLatest(int $perPage = 12): LengthAwarePaginator
    {
        return $this->articles->paginateLatest($perPage);
    }

    public function loadDetails(Article $article): Article
    {
        return $this->articles->loadRelations($article);
    }
}
