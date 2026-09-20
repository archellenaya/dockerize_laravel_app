@extends('layouts.app')

@section('title', 'Your Dashboard')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Saved Articles</h1>

    @if ($articles->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="text-lg font-medium text-gray-900">You haven't saved any articles yet</p>
            <p class="mt-2 text-sm text-gray-500">
                Browse the <a href="{{ route('articles.index') }}" class="font-medium text-blue-700 hover:underline">latest articles</a>
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
