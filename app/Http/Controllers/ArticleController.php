<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Contracts\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::with(['category', 'source'])
            ->latest('published_at')
            ->paginate(12);

        return view('articles.index', ['articles' => $articles]);
    }

    public function show(Article $article): View
    {
        $article->load(['category', 'source']);

        return view('articles.show', ['article' => $article]);
    }
}
