<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; }
            h1 { font-size: 18px; margin-bottom: 4px; }
            p.meta { color: #6b7280; margin-top: 0; margin-bottom: 16px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; vertical-align: top; }
            th { background-color: #f3f4f6; font-size: 10px; text-transform: uppercase; color: #4b5563; }
            .title { font-weight: bold; }
            .muted { color: #6b7280; font-size: 10px; }
        </style>
    </head>
    <body>
        <h1>Articles Export</h1>
        <p class="meta">Generated {{ now()->format('F j, Y \a\t g:i A') }} &middot; {{ $articles->count() }} article(s)</p>

        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Source</th>
                    <th>Published</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($articles as $article)
                    <tr>
                        <td>
                            <div class="title">{{ $article->title }}</div>
                            @if ($article->description)
                                <div class="muted">{{ \Illuminate\Support\Str::limit($article->description, 160) }}</div>
                            @endif
                            <div class="muted">{{ $article->url }}</div>
                        </td>
                        <td>{{ $article->category?->name ?? '—' }}</td>
                        <td>{{ $article->source?->name ?? '—' }}</td>
                        <td>{{ $article->published_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
