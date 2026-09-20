@extends('layouts.app')

@section('title', 'Something went wrong')

@section('content')
    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
        <p class="text-lg font-medium text-gray-900 dark:text-gray-100">Something went wrong on our end</p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Please try again in a moment. If the problem persists, let us know.
        </p>
        <a href="{{ route('articles.index') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
            &larr; Back to all articles
        </a>
    </div>
@endsection
