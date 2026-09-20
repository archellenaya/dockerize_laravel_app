<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Contracts\BookmarkServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BookmarkServiceInterface $bookmarks,
    ) {}

    public function index(Request $request): View
    {
        return view('dashboard', [
            'articles' => $this->bookmarks->listSavedForUser($request->user()->id),
        ]);
    }
}
