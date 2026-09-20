<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Contracts\ArticleServiceInterface;
use App\Support\ArticleFilters;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports the currently filtered article list - reads the same query
 * string ArticleList's #[Url]-bound properties produce, so an "Export"
 * link on that page carries the visitor's current filters over exactly.
 */
class ArticleExportController extends Controller
{
    private const EXPORT_LIMIT = 1000;

    public function __construct(
        private readonly ArticleServiceInterface $articles,
    ) {}

    public function csv(Request $request): StreamedResponse
    {
        $articles = $this->articles->exportable(ArticleFilters::fromQuery($request->query()), self::EXPORT_LIMIT);

        return response()->streamDownload(function () use ($articles) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Title', 'Description', 'Author', 'Category', 'Source', 'Published At', 'Reading Time (min)', 'URL']);

            foreach ($articles as $article) {
                fputcsv($handle, [
                    $article->title,
                    $article->description,
                    $article->author,
                    $article->category?->name,
                    $article->source?->name,
                    $article->published_at?->toDateTimeString(),
                    $article->reading_time_minutes,
                    $article->url,
                ]);
            }

            fclose($handle);
        }, 'articles.csv', ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request): Response
    {
        $articles = $this->articles->exportable(ArticleFilters::fromQuery($request->query()), self::EXPORT_LIMIT);

        return Pdf::loadView('exports.articles-pdf', ['articles' => $articles])
            ->download('articles.pdf');
    }
}
