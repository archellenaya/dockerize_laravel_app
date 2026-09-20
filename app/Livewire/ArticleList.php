<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Article;
use App\Services\Contracts\ArticleServiceInterface;
use App\Services\Contracts\BookmarkServiceInterface;
use App\Support\ArticleFilters;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The main article-browsing page: live search/filtering with no full page
 * reload. Filters are bound to the query string so a filtered view is
 * shareable/bookmarkable as a URL, and are the single source of truth
 * that gets handed to ArticleServiceInterface - this component holds no
 * article-fetching logic of its own, only UI state and orchestration,
 * consistent with how ArticleController used the same service.
 *
 * The view uses a classic `@extends('layouts.app')` rather than the
 * `#[Layout]` attribute, because that attribute expects the layout to be
 * a Blade *component* (rendering `{{ $slot }}`) - this app's layout is a
 * traditional `@yield('content')` template, same as every other page.
 */
class ArticleList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category = '';

    #[Url(history: true)]
    public string $source = '';

    #[Url(history: true)]
    public string $from = '';

    #[Url(history: true)]
    public string $to = '';

    /**
     * Reset to page 1 whenever a filter changes - otherwise the user
     * could land on, say, page 4 of a filtered set that only has one
     * page of results.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'source', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'category', 'source', 'from', 'to');
        $this->resetPage();
    }

    public function toggleBookmark(Article $article, BookmarkServiceInterface $bookmarks): void
    {
        $user = auth()->user();

        if (! $user instanceof Authenticatable) {
            $this->redirectRoute('login');

            return;
        }

        if ($bookmarks->isSaved((int) $user->getAuthIdentifier(), $article)) {
            $bookmarks->unsave((int) $user->getAuthIdentifier(), $article);
        } else {
            $bookmarks->save((int) $user->getAuthIdentifier(), $article);
        }
    }

    #[Computed]
    public function articles(): LengthAwarePaginator
    {
        $filters = new ArticleFilters(
            search: $this->search !== '' ? $this->search : null,
            categorySlug: $this->category !== '' ? $this->category : null,
            sourceSlug: $this->source !== '' ? $this->source : null,
            from: $this->from !== '' ? $this->from : null,
            to: $this->to !== '' ? $this->to : null,
        );

        return app(ArticleServiceInterface::class)->listLatest(12, $filters);
    }

    #[Computed]
    public function categories(): Collection
    {
        return app(ArticleServiceInterface::class)->availableCategories();
    }

    #[Computed]
    public function sources(): Collection
    {
        return app(ArticleServiceInterface::class)->availableSources();
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function bookmarkedIds(): array
    {
        $user = auth()->user();

        if (! $user instanceof Authenticatable) {
            return [];
        }

        return app(BookmarkServiceInterface::class)
            ->filterSavedIds((int) $user->getAuthIdentifier(), $this->articles());
    }

    public function render(): View
    {
        return view('livewire.article-list');
    }
}
