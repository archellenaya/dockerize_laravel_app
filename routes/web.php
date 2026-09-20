<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleExportController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryFollowController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArticleController::class, 'index'])->name('articles.index');

// Registered before the {article} wildcard below so "export" isn't
// swallowed as a route-model-binding parameter.
Route::get('/articles/export/csv', [ArticleExportController::class, 'csv'])->name('articles.export.csv');
Route::get('/articles/export/pdf', [ArticleExportController::class, 'pdf'])->name('articles.export.pdf');

Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/articles/{article}/bookmark', [BookmarkController::class, 'store'])->name('bookmarks.store');
    Route::delete('/articles/{article}/bookmark', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');

    Route::post('/categories/{category}/follow', [CategoryFollowController::class, 'store'])->name('categories.follow');
    Route::delete('/categories/{category}/follow', [CategoryFollowController::class, 'destroy'])->name('categories.unfollow');
});

require __DIR__.'/auth.php';
