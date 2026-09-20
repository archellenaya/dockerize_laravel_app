<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\Contracts\ArticleServiceInterface;
use App\Support\ArticleFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression tests for the SQL injection check performed manually against
 * the search/category/source filters (App\Repositories\EloquentArticleRepository).
 * The app is not vulnerable because every query goes through Eloquent's
 * query builder, which parameter-binds values rather than concatenating
 * them into the SQL string - these tests pin that behavior down so it
 * can't regress silently (e.g. someone "optimizing" a filter into
 * whereRaw/DB::raw with string interpolation).
 */
class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_union_select_payload_in_the_search_filter_is_not_executed_as_sql(): void
    {
        // Seed something an attacker might try to exfiltrate via UNION.
        $secretUser = User::factory()->create(['email' => 'secret-admin@example.com']);
        Article::factory()->create(['title' => 'A normal public article']);

        $payload = "nomatch' UNION SELECT id, name, email, email, password, 1, 1, 1, 1, 1, 1 FROM users -- ";

        $response = $this->get('/?'.http_build_query(['q' => $payload]));

        $response->assertOk();
        $response->assertDontSee($secretUser->email);
        $response->assertSee('No articles match your filters');
    }

    public function test_the_search_filter_binds_the_raw_payload_as_a_single_parameter(): void
    {
        DB::enableQueryLog();

        $payload = "x' OR '1'='1";
        $filters = new ArticleFilters(search: $payload);
        app(ArticleServiceInterface::class)->listLatest(12, $filters);

        $log = collect(DB::getQueryLog())->firstWhere(fn ($entry) => str_contains($entry['query'], 'like'));

        $this->assertNotNull($log, 'Expected a LIKE query to have run.');
        // The compiled SQL must use a placeholder, never the raw payload.
        $this->assertStringNotContainsString($payload, $log['query']);
        $this->assertStringContainsString('?', $log['query']);
        // The payload should appear only inside the bound parameter values.
        $this->assertStringContainsString($payload, $log['bindings'][0]);
    }

    public function test_an_or_1_equals_1_payload_in_the_category_filter_does_not_bypass_filtering(): void
    {
        $category = Category::factory()->create(['slug' => 'technology']);
        Article::factory()->create(['category_id' => $category->id, 'title' => 'Real Technology Article']);
        Article::factory()->create(['title' => 'Unrelated Article']);

        $response = $this->get('/?'.http_build_query(['category' => "technology' OR '1'='1"]));

        $response->assertOk();
        // A successful bypass would return both articles; it must return neither.
        $response->assertDontSee('Real Technology Article');
        $response->assertDontSee('Unrelated Article');
    }
}
