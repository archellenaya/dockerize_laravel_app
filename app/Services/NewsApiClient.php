<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NewsApiClient
{
    protected string $baseUrl;

    protected string $apiKey;

    protected int $timeout;

    protected ?string $lastErrorMessage = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.newsapi.base_url', 'https://newsapi.org/v2'), '/');
        $this->apiKey = (string) config('services.newsapi.api_key', '');
        $this->timeout = (int) config('services.newsapi.timeout', 15);
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    /**
     * Fetch top headlines for one or more sources/categories.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $sources, array $categories, ?string $from = null, ?string $to = null, int $limit = 10): array
    {
        $this->lastErrorMessage = null;

        if (blank($this->apiKey)) {
            $this->lastErrorMessage = 'The NEWSAPI_API_KEY environment variable is not configured.';
            Log::error('NewsAPI configuration missing: NEWSAPI_API_KEY is not set.');

            throw new RuntimeException($this->lastErrorMessage);
        }

        $sources = array_values(array_filter(array_map('trim', $sources), fn ($source) => filled($source)));
        $categories = array_values(array_filter(array_map('trim', $categories), fn ($category) => filled($category)));

        if (empty($sources) && empty($categories)) {
            $categories = ['general'];
        }

        $requests = $this->buildRequests($sources, $categories);
        $articles = [];
        $seenUrls = [];

        foreach ($requests as $request) {
            if (count($articles) >= $limit) {
                break;
            }

            $response = $this->sendRequest($request['source'] ?? null, $request['category'] ?? null, $from, $to, min(20, max(1, $limit - count($articles))));

            if (! $response['success']) {
                $this->lastErrorMessage = $response['message'] ?? $this->lastErrorMessage;

                continue;
            }

            foreach ($response['articles'] as $article) {
                $normalized = $this->normalizeArticle($article, $request['category'] ?? null);

                if ($normalized === null) {
                    continue;
                }

                $url = $normalized['url'] ?? null;
                if ($url === null || isset($seenUrls[$url])) {
                    continue;
                }

                $seenUrls[$url] = true;
                $articles[] = $normalized;

                if (count($articles) >= $limit) {
                    break 2;
                }
            }
        }

        return $articles;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function buildRequests(array $sources, array $categories): array
    {
        $requests = [];

        if (empty($sources) && empty($categories)) {
            return [['category' => 'general']];
        }

        if (! empty($sources)) {
            foreach ($sources as $source) {
                $requests[] = ['source' => $source];
            }
        }

        if (! empty($categories)) {
            foreach ($categories as $category) {
                $requests[] = ['category' => $category];
            }
        }

        return $requests;
    }

    /**
     * @return array{success: bool, articles: array<int, array<string, mixed>>, status: int|null, message: string|null}
     */
    protected function sendRequest(?string $source, ?string $category, ?string $from, ?string $to, int $pageSize): array
    {
        $params = [
            'pageSize' => $pageSize,
            'language' => config('services.newsapi.language', 'en'),
        ];

        if (! blank($source)) {
            $params['sources'] = $source;
        }

        if (! blank($category)) {
            $params['category'] = $category;
        }

        if (! blank($from)) {
            $params['from'] = $this->normalizeDate($from);
        }

        if (! blank($to)) {
            $params['to'] = $this->normalizeDate($to);
        }

        $requestContext = [
            'source' => $source,
            'category' => $category,
            'from' => $params['from'] ?? null,
            'to' => $params['to'] ?? null,
            'page_size' => $pageSize,
        ];

        Log::info('NewsAPI request started', $requestContext);

        try {
            $response = $this->client()
                ->acceptJson()
                ->get($this->baseUrl.'/top-headlines', $params);

            $status = $response->status();
            $json = $response->json();
            $message = data_get($json, 'message');

            if ($response->successful()) {
                $articles = $json['articles'] ?? [];

                Log::info('NewsAPI request succeeded', [
                    ...$requestContext,
                    'status' => $status,
                    'articles_count' => count($articles),
                ]);

                return ['success' => true, 'articles' => $articles, 'status' => $status, 'message' => $message];
            }

            if ($status === 429) {
                Log::warning('NewsAPI rate limit reached', [
                    ...$requestContext,
                    'status' => $status,
                    'message' => $message ?? 'NewsAPI rate limit reached',
                ]);

                return ['success' => false, 'articles' => [], 'status' => $status, 'message' => $message ?? 'rate limit reached'];
            }

            Log::warning('NewsAPI request failed', [
                ...$requestContext,
                'status' => $status,
                'message' => $message ?? 'Unknown NewsAPI error',
            ]);

            return ['success' => false, 'articles' => [], 'status' => $status, 'message' => $message ?? 'Unknown NewsAPI error'];
        } catch (ConnectionException $exception) {
            Log::error('NewsAPI connection error', [
                ...$requestContext,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'articles' => [], 'status' => null, 'message' => $exception->getMessage()];
        } catch (\Throwable $exception) {
            Log::error('NewsAPI unexpected error', [
                ...$requestContext,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'articles' => [], 'status' => null, 'message' => $exception->getMessage()];
        }
    }

    protected function client(): PendingRequest
    {
        return Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
        ])->timeout($this->timeout);
    }

    protected function normalizeDate(string $value): string
    {
        return Carbon::parse($value)->toDateString();
    }

    /**
     * @param  array<string, mixed>  $article
     * @return array<string, mixed>|null
     */
    protected function normalizeArticle(array $article, ?string $requestedCategory = null): ?array
    {
        $title = trim((string) ($article['title'] ?? ''));
        $url = trim((string) ($article['url'] ?? ''));

        if ($title === '' || $url === '') {
            return null;
        }

        return [
            'title' => $title,
            'source' => data_get($article, 'source.name', 'Unknown'),
            'author' => data_get($article, 'author', null),
            'description' => data_get($article, 'description', null),
            'url' => $url,
            'url_to_image' => data_get($article, 'urlToImage', null),
            'published_at' => data_get($article, 'publishedAt', null),
            'content' => data_get($article, 'content', null),
            // NewsAPI's response payload does not echo back a category per
            // article, so fall back to the category that was requested.
            'category' => data_get($article, 'category', null) ?? $requestedCategory,
        ];
    }
}
