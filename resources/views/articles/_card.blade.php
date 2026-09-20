@props(['article', 'isBookmarked' => false])

<article class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
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
                @include('articles._bookmark-button', ['article' => $article, 'isBookmarked' => $isBookmarked])
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
