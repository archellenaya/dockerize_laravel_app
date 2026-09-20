@extends('layouts.app')

@section('title', 'Your Dashboard')

@section('content')
    <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">Followed Categories</h1>
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Get an email whenever a new article is added to a category you follow.
    </p>

    <div class="mb-10 flex flex-wrap gap-2">
        @forelse ($categories as $category)
            @if (in_array($category->id, $followedCategoryIds, true))
                <form method="POST" action="{{ route('categories.unfollow', $category) }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900"
                    >
                        {{ $category->name }}
                        <span aria-hidden="true">&times;</span>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('categories.follow', $category) }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        + {{ $category->name }}
                    </button>
                </form>
            @endif
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No categories yet.</p>
        @endforelse
    </div>

    <h2 class="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Saved Articles</h2>

    @if ($articles->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
            <p class="text-lg font-medium text-gray-900 dark:text-gray-100">You haven't saved any articles yet</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Browse the <a href="{{ route('articles.index') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">latest articles</a>
                and hit "Save" on the ones you want to come back to.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($articles as $article)
                @include('articles._card', ['article' => $article, 'isBookmarked' => true])
            @endforeach
        </div>

        <div class="mt-8">
            {{ $articles->links() }}
        </div>
    @endif
@endsection
