<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Contracts\ArticleServiceInterface;
use Illuminate\Contracts\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleServiceInterface $articles,
    ) {}

    public function index(): View
    {
        return view('articles.index', [
            'articles' => $this->articles->listLatest(),
        ]);
    }

    /**
     * Laravel's route-model-binding resolves $article (or throws a
     * ModelNotFoundException, rendered as the custom 404 view) before
     * this method runs; the service is only responsible for loading the
     * relations the detail view needs.
     */
    public function show(Article $article): View
    {
        return view('articles.show', [
            'article' => $this->articles->loadDetails($article),
        ]);
    }
}
