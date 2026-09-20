<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Latest Articles</h1>

        <div class="flex items-center gap-4">
            <div wire:loading class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4 animate-spin text-gray-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Updating&hellip;
            </div>

            @php
                $exportQuery = array_filter([
                    'q' => $search,
                    'category' => $category,
                    'source' => $source,
                    'from' => $from,
                    'to' => $to,
                ]);
            @endphp
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('articles.export.csv', $exportQuery) }}" class="font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                    Export CSV
                </a>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <a href="{{ route('articles.export.pdf', $exportQuery) }}" class="font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="lg:col-span-2">
            <label for="search" class="block text-xs font-medium text-gray-500 dark:text-gray-400">Search</label>
            <input
                id="search"
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="Title or description&hellip;"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
        </div>

        <div>
            <label for="category" class="block text-xs font-medium text-gray-500 dark:text-gray-400">Category</label>
            <select
                id="category"
                wire:model.live="category"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
                <option value="">All categories</option>
                @foreach ($this->categories as $option)
                    <option value="{{ $option->slug }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="source" class="block text-xs font-medium text-gray-500 dark:text-gray-400">Source</label>
            <select
                id="source"
                wire:model.live="source"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
                <option value="">All sources</option>
                @foreach ($this->sources as $option)
                    <option value="{{ $option->slug }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
            <div class="flex-1">
                <label for="from" class="block text-xs font-medium text-gray-500 dark:text-gray-400">From</label>
                <input
                    id="from"
                    type="date"
                    wire:model.live="from"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                >
            </div>
            <div class="flex-1">
                <label for="to" class="block text-xs font-medium text-gray-500 dark:text-gray-400">To</label>
                <input
                    id="to"
                    type="date"
                    wire:model.live="to"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                >
            </div>
        </div>

        @if ($search !== '' || $category !== '' || $source !== '' || $from !== '' || $to !== '')
            <div class="lg:col-span-5">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="text-xs font-medium text-blue-700 hover:underline dark:text-blue-400"
                >
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    <div
        wire:loading.class="opacity-50"
        wire:target="search, category, source, from, to, resetFilters, gotoPage, nextPage, previousPage"
        class="transition-opacity duration-200"
    >
        @if ($this->articles->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
                @if ($search !== '' || $category !== '' || $source !== '' || $from !== '' || $to !== '')
                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No articles match your filters</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Try widening your search, or
                        <button type="button" wire:click="resetFilters" class="font-medium text-blue-700 hover:underline dark:text-blue-400">clear all filters</button>.
                    </p>
                @else
                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No articles yet</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Run <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-800">php artisan news:fetch</code> to pull in the latest headlines.
                    </p>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->articles as $article)
                    <article wire:key="article-{{ $article->id }}" class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <a href="{{ route('articles.show', $article) }}" class="relative block aspect-video w-full overflow-hidden bg-gray-100 dark:bg-gray-800">
                            @if ($article->image_url)
                                <img
                                    src="{{ $article->image_url }}"
                                    alt="{{ $article->title }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover"
                                    onerror="this.nextElementSibling.classList.remove('hidden'); this.remove();"
                                >
                            @endif
                            <div class="{{ $article->image_url ? 'hidden ' : '' }}flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-10 w-10">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18-13.5h16.5a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5zm10.5 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </div>
                        </a>

                        <div class="flex flex-1 flex-col p-4">
                            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                                @if ($article->category)
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                        {{ $article->category->name }}
                                    </span>
                                @endif
                                @if ($article->source)
                                    <span class="text-gray-500 dark:text-gray-400">{{ $article->source->name }}</span>
                                @endif
                            </div>

                            <div class="mb-2 flex items-start justify-between gap-2">
                                <h2 class="line-clamp-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                                    <a href="{{ route('articles.show', $article) }}" class="hover:underline">
                                        {{ $article->title }}
                                    </a>
                                </h2>

                                <div class="shrink-0">
                                    @auth
                                        @if (in_array($article->id, $this->bookmarkedIds, true))
                                            <button
                                                type="button"
                                                wire:click="toggleBookmark({{ $article->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleBookmark({{ $article->id }})"
                                                class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 disabled:opacity-50 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5">
                                                    <path d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" />
                                                </svg>
                                                Saved
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="toggleBookmark({{ $article->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleBookmark({{ $article->id }})"
                                                class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                                </svg>
                                                Save
                                            </button>
                                        @endif
                                    @endauth
                                </div>
                            </div>

                            @if ($article->description)
                                <p class="mb-3 line-clamp-3 flex-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $article->description }}
                                </p>
                            @endif

                            <p class="mt-auto text-xs text-gray-400 dark:text-gray-500">
                                @if ($article->published_at)
                                    {{ $article->published_at->format('M j, Y') }}
                                @else
                                    Publication date unknown
                                @endif
                                &middot; {{ $article->reading_time_minutes }} min read
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $this->articles->links() }}
            </div>
        @endif
    </div>
</div>
