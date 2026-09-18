<?php

namespace App\Console\Commands;

use App\Services\ArticleImportService;
use App\Services\NewsApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch
        {--sources= : Comma-separated news sources, e.g. cnn,bbc-news}
        {--categories= : Comma-separated categories, e.g. technology,business}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--limit=10 : Maximum number of articles to fetch}
        {--timeout=15 : API timeout in seconds}
        {--force : Continue even when some requests fail}';

    protected $description = 'Fetch recent news articles from configured news sources and categories via NewsAPI';

    public function handle(NewsApiClient $client, ArticleImportService $importer): int
    {
        try {
            $sources = $this->parseCsvOption('sources');
            $categories = $this->parseCsvOption('categories');
            $limit = max(1, (int) $this->option('limit'));
            $from = $this->option('from');
            $to = $this->option('to');

            $this->info('Fetching news articles...');
            $this->info('Sources: ' . ($sources ? implode(', ', $sources) : 'all'));
            foreach ($sources as $source) {
                $this->info('Requested source filter: ' . $source);
            }
            $this->info('Categories: ' . ($categories ? implode(', ', $categories) : 'all'));
            foreach ($categories as $category) {
                $this->info('Requested category filter: ' . $category);
            }
            $this->info('Date range: ' . ($from ?? 'n/a') . ' to ' . ($to ?? 'n/a'));

            $articles = $client->fetch($sources, $categories, $from, $to, $limit);

            if (empty($articles)) {
                $message = $client->getLastErrorMessage() ?: 'No articles were returned from the API for the requested filters.';
                $this->warn($message);
                Log::warning('News fetch completed without articles.', [
                    'sources' => $sources,
                    'categories' => $categories,
                    'from' => $from,
                    'to' => $to,
                    'limit' => $limit,
                    'message' => $message,
                ]);

                return self::SUCCESS;
            }

            $this->table(
                ['Title', 'Source', 'Published At', 'URL'],
                array_map(fn ($article) => [
                    Str::limit($article['title'], 80),
                    $article['source'] ?? 'Unknown',
                    $article['published_at'] ?? 'n/a',
                    $article['url'],
                ], $articles)
            );

            $this->info(sprintf('Fetched %d articles.', count($articles)));
            Log::info('News fetch succeeded', [
                'source_count' => count($articles),
                'sources' => $sources,
                'categories' => $categories,
            ]);

            $result = $importer->importMany($articles);

            $this->info(sprintf(
                'Saved %d new article(s), skipped %d duplicate(s), rejected %d invalid record(s).',
                $result['created'],
                $result['duplicates'],
                $result['invalid'],
            ));

            foreach ($result['errors'] as $error) {
                $this->warn(sprintf('Rejected %s: %s', $error['url'] ?? 'unknown URL', implode(' ', $error['errors'])));
            }

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            Log::error('News fetch failed.', ['message' => $exception->getMessage()]);

            return self::FAILURE;
        }
    }

    protected function parseCsvOption(string $option): array
    {
        $value = $this->option($option);

        if (blank($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($item) => $item !== ''));
    }
}
