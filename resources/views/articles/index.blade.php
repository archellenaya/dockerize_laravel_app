@extends('layouts.app')

@section('title', 'Latest News')

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Latest Articles</h1>

    @if ($articles->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <p class="text-lg font-medium text-gray-900">No articles yet</p>
            <p class="mt-2 text-sm text-gray-500">
                Run <code class="rounded bg-gray-100 px-1.5 py-0.5">php artisan news:fetch</code> to pull in the latest headlines.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($articles as $article)
                @include('articles._card', ['article' => $article])
            @endforeach
        </div>

        <div class="mt-8">
            {{ $articles->links() }}
        </div>
    @endif
@endsection
