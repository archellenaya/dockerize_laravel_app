<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsFetchCommandTest extends TestCase
{
    use RefreshDatabase;

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
            ->expectsOutputToContain('bbc-news')
            ->expectsOutputToContain('Saved 2 new article(s), skipped 0 duplicate(s), rejected 0 invalid record(s).');

        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('articles', [
            'title' => 'Tech headline',
            'url' => 'https://example.com/tech',
        ]);
    }

    public function test_it_skips_articles_already_saved(): void
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Tech headline',
                        'source' => ['name' => 'CNN'],
                        'publishedAt' => '2026-09-18T12:00:00Z',
                        'url' => 'https://example.com/tech?utm_source=newsletter',
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('news:fetch --sources=cnn --limit=1')->assertSuccessful();
        $this->artisan('news:fetch --sources=cnn --limit=1')
            ->assertSuccessful()
            ->expectsOutputToContain('Saved 0 new article(s), skipped 1 duplicate(s), rejected 0 invalid record(s).');

        // The tracking query param is stripped, so both fetches resolve to the same URL.
        $this->assertDatabaseCount('articles', 1);
        $this->assertDatabaseHas('articles', ['url' => 'https://example.com/tech']);
    }

    public function test_it_rejects_articles_with_invalid_data(): void
    {
        Http::fake([
            'https://newsapi.org/v2/top-headlines*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Future dated headline',
                        'source' => ['name' => 'CNN'],
                        'publishedAt' => now()->addYear()->toIso8601String(),
                        'url' => 'https://example.com/future',
                    ],
                    [
                        'title' => 'Bad url headline',
                        'source' => ['name' => 'CNN'],
                        'publishedAt' => '2026-09-18T12:00:00Z',
                        'url' => 'not-a-valid-url',
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('news:fetch --sources=cnn --limit=2')
            ->assertSuccessful()
            ->expectsOutputToContain('Saved 0 new article(s), skipped 0 duplicate(s), rejected 2 invalid record(s).');

        $this->assertDatabaseCount('articles', 0);
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
