<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Contracts\CategoryFollowServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryFollowController extends Controller
{
    public function __construct(
        private readonly CategoryFollowServiceInterface $follows,
    ) {}

    public function store(Request $request, Category $category): RedirectResponse
    {
        $this->follows->follow($request->user()->id, $category);

        return back()->with('status', "You'll get an email when a new {$category->name} article is added.");
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->follows->unfollow($request->user()->id, $category);

        return back()->with('status', "Unfollowed {$category->name}.");
    }
}
