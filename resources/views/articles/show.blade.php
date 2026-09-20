@extends('layouts.app')

@section('title', $article->title)

@section('content')
    <a href="{{ route('articles.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline">
        &larr; Back to all articles
    </a>

    <article class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @if ($article->image_url)
            <img
                src="{{ $article->image_url }}"
                alt="{{ $article->title }}"
                class="max-h-[420px] w-full object-cover"
                onerror="this.remove();"
            >
        @endif

        <div class="p-6 sm:p-8">
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                @if ($article->category)
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-800">
                        {{ $article->category->name }}
                    </span>
                @endif
                @if ($article->source)
                    <span class="text-gray-500">{{ $article->source->name }}</span>
                @endif
            </div>

            <div class="mb-3 flex items-start justify-between gap-4">
                <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $article->title }}</h1>

                <div class="shrink-0">
                    @auth
                        @include('articles._bookmark-button', ['article' => $article, 'isBookmarked' => $isBookmarked])
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-medium text-blue-700 hover:underline">
                            Log in to save
                        </a>
                    @endauth
                </div>
            </div>

            <p class="mb-6 text-sm text-gray-500">
                @if ($article->author)
                    By {{ $article->author }} &middot;
                @endif
                @if ($article->published_at)
                    {{ $article->published_at->format('F j, Y \a\t g:i A') }}
                @else
                    Publication date unknown
                @endif
            </p>

            @if ($article->description)
                <p class="mb-4 text-lg text-gray-700">{{ $article->description }}</p>
            @endif

            @if ($article->content)
                <p class="whitespace-pre-line text-base leading-relaxed text-gray-800">{{ $article->content }}</p>
            @endif

            <div class="mt-8 border-t border-gray-200 pt-6">
                <a
                    href="{{ $article->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline"
                >
                    Read the full article at the original source &rarr;
                </a>
            </div>
        </div>
    </article>
@endsection
