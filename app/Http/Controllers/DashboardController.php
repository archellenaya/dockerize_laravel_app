<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Contracts\ArticleServiceInterface;
use App\Services\Contracts\BookmarkServiceInterface;
use App\Services\Contracts\CategoryFollowServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BookmarkServiceInterface $bookmarks,
        private readonly ArticleServiceInterface $articles,
        private readonly CategoryFollowServiceInterface $follows,
    ) {}

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $categories = $this->articles->availableCategories();

        return view('dashboard', [
            'articles' => $this->bookmarks->listSavedForUser($userId),
            'categories' => $categories,
            'followedCategoryIds' => $this->follows->followedIds($userId, $categories),
        ]);
    }
}
