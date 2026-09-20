<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/articles/{article}/bookmark', [BookmarkController::class, 'store'])->name('bookmarks.store');
    Route::delete('/articles/{article}/bookmark', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
});

require __DIR__.'/auth.php';
