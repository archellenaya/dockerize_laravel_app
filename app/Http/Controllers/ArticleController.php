<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Contracts\ArticleServiceInterface;
use App\Services\Contracts\BookmarkServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleServiceInterface $articles,
        private readonly BookmarkServiceInterface $bookmarks,
    ) {}

    public function index(Request $request): View
    {
        $articles = $this->articles->listLatest();

        return view('articles.index', [
            'articles' => $articles,
            'bookmarkedIds' => $this->bookmarkedIdsFor($request, $articles),
        ]);
    }

    /**
     * Laravel's route-model-binding resolves $article (or throws a
     * ModelNotFoundException, rendered as the custom 404 view) before
     * this method runs; the service is only responsible for loading the
     * relations the detail view needs.
     */
    public function show(Request $request, Article $article): View
    {
        $article = $this->articles->loadDetails($article);

        return view('articles.show', [
            'article' => $article,
            'isBookmarked' => $request->user() && $this->bookmarks->isSaved($request->user()->id, $article),
        ]);
    }

    /**
     * @param  iterable<Article>  $articles
     * @return array<int, int>
     */
    private function bookmarkedIdsFor(Request $request, iterable $articles): array
    {
        if (! $request->user()) {
            return [];
        }

        return $this->bookmarks->filterSavedIds($request->user()->id, $articles);
    }
}
