<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Contracts\BookmarkServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly BookmarkServiceInterface $bookmarks,
    ) {}

    public function store(Request $request, Article $article): RedirectResponse
    {
        $this->bookmarks->save($request->user()->id, $article);

        return back()->with('status', 'Article saved to your dashboard.');
    }

    public function destroy(Request $request, Article $article): RedirectResponse
    {
        $this->bookmarks->unsave($request->user()->id, $article);

        return back()->with('status', 'Article removed from your dashboard.');
    }
}
