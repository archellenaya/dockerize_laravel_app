@extends('layouts.app')

@section('title', 'Something went wrong')

@section('content')
    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
        <p class="text-lg font-medium text-gray-900">Something went wrong on our end</p>
        <p class="mt-2 text-sm text-gray-500">
            Please try again in a moment. If the problem persists, let us know.
        </p>
        <a href="{{ route('articles.index') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline">
            &larr; Back to all articles
        </a>
    </div>
@endsection
