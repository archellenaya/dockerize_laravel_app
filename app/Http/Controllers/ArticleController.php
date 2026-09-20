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

    /**
     * The listing itself is a Livewire component (App\Livewire\ArticleList)
     * embedded in the view - it fetches and filters its own data, so this
     * action's only job is to render the page shell around it.
     */
    public function index(): View
    {
        return view('articles.index');
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
}
