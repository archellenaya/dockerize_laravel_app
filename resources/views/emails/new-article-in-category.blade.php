<x-mail::message>
# New in {{ $article->category?->name }}

**{{ $article->title }}**

@if ($article->description)
{{ $article->description }}
@endif

{{ $article->reading_time_minutes }} min read @if ($article->source) &middot; {{ $article->source->name }} @endif

<x-mail::button :url="route('articles.show', $article)">
Read the article
</x-mail::button>

You're receiving this because you followed the {{ $article->category?->name }} category.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
