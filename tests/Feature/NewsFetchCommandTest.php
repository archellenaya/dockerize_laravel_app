<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsFetchCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.newsapi.api_key', 'test-key');
    }

    public function test_it_fetches_articles_for_multiple_sources_and_categories(): void
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Tech headline',
                        'source' => ['name' => 'CNN'],
                        'publishedAt' => '2026-09-18T12:00:00Z',
                        'url' => 'https://example.com/tech',
                    ],
                    [
                        'title' => 'Business headline',
                        'source' => ['name' => 'BBC'],
                        'publishedAt' => '2026-09-18T13:00:00Z',
                        'url' => 'https://example.com/business',
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('news:fetch --sources=cnn,bbc-news --categories=technology,business --limit=2')
            ->assertSuccessful()
            ->expectsOutputToContain('Fetched 2 articles')
            ->expectsOutputToContain('cnn')
            ->expectsOutputToContain('bbc-news');
    }

    public function test_it_handles_rate_limit_gracefully(): void
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'message' => 'rate limit reached',
            ], 429),
        ]);

        $this->artisan('news:fetch --sources=cnn --limit=5')
            ->assertExitCode(0)
            ->expectsOutputToContain('rate limit');
    }
}
