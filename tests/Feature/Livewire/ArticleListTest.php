<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ArticleList;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_articles_with_no_filters_applied(): void
    {
        Article::factory()->create(['title' => 'Alpha Headline']);
        Article::factory()->create(['title' => 'Beta Headline']);

        Livewire::test(ArticleList::class)
            ->assertSee('Alpha Headline')
            ->assertSee('Beta Headline');
    }

    public function test_it_filters_by_category(): void
    {
        $tech = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $sports = Category::factory()->create(['name' => 'Sports', 'slug' => 'sports']);

        Article::factory()->create(['category_id' => $tech->id, 'title' => 'Tech Headline']);
        Article::factory()->create(['category_id' => $sports->id, 'title' => 'Sports Headline']);

        Livewire::test(ArticleList::class)
            ->set('category', 'technology')
            ->assertSee('Tech Headline')
            ->assertDontSee('Sports Headline');
    }

    public function test_it_filters_by_source(): void
    {
        $bbc = Source::factory()->create(['name' => 'BBC News', 'slug' => 'bbc-news']);
        $cnn = Source::factory()->create(['name' => 'CNN', 'slug' => 'cnn']);

        Article::factory()->create(['source_id' => $bbc->id, 'title' => 'BBC Headline']);
        Article::factory()->create(['source_id' => $cnn->id, 'title' => 'CNN Headline']);

        Livewire::test(ArticleList::class)
            ->set('source', 'bbc-news')
            ->assertSee('BBC Headline')
            ->assertDontSee('CNN Headline');
    }

    public function test_it_filters_by_search_term_against_title_and_description(): void
    {
        Article::factory()->create(['title' => 'A Unique Searchable Headline', 'description' => null]);
        Article::factory()->create(['title' => 'Something Else Entirely', 'description' => 'Nothing special']);

        Livewire::test(ArticleList::class)
            ->set('search', 'Searchable')
            ->assertSee('A Unique Searchable Headline')
            ->assertDontSee('Something Else Entirely');
    }

    public function test_it_filters_by_date_range(): void
    {
        Article::factory()->create(['title' => 'January Article', 'published_at' => '2026-01-15']);
        Article::factory()->create(['title' => 'June Article', 'published_at' => '2026-06-15']);

        Livewire::test(ArticleList::class)
            ->set('from', '2026-01-01')
            ->set('to', '2026-01-31')
            ->assertSee('January Article')
            ->assertDontSee('June Article');
    }

    public function test_combining_multiple_filters_narrows_results_further(): void
    {
        $tech = Category::factory()->create(['slug' => 'technology']);

        Article::factory()->create(['category_id' => $tech->id, 'title' => 'Matches Both Filters']);
        Article::factory()->create(['category_id' => $tech->id, 'title' => 'Matches Category Only']);

        Livewire::test(ArticleList::class)
            ->set('category', 'technology')
            ->set('search', 'Both Filters')
            ->assertSee('Matches Both Filters')
            ->assertDontSee('Matches Category Only');
    }

    public function test_changing_a_filter_resets_pagination_to_page_one(): void
    {
        Article::factory()->count(15)->create();

        $component = Livewire::test(ArticleList::class)
            ->call('gotoPage', 2);

        $this->assertSame(2, $component->instance()->getPage());

        $component->set('search', 'anything');

        $this->assertSame(1, $component->instance()->getPage());
    }

    public function test_reset_filters_clears_every_filter(): void
    {
        Livewire::test(ArticleList::class)
            ->set('search', 'foo')
            ->set('category', 'technology')
            ->set('source', 'cnn')
            ->set('from', '2026-01-01')
            ->set('to', '2026-01-31')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('category', '')
            ->assertSet('source', '')
            ->assertSet('from', '')
            ->assertSet('to', '');
    }

    public function test_a_guest_toggling_a_bookmark_is_redirected_to_login(): void
    {
        $article = Article::factory()->create();

        Livewire::test(ArticleList::class)
            ->call('toggleBookmark', $article->id)
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('article_bookmarks', 0);
    }

    public function test_an_authenticated_user_can_toggle_a_bookmark(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        Livewire::actingAs($user)
            ->test(ArticleList::class)
            ->call('toggleBookmark', $article->id);

        $this->assertDatabaseHas('article_bookmarks', [
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        Livewire::actingAs($user)
            ->test(ArticleList::class)
            ->call('toggleBookmark', $article->id);

        $this->assertDatabaseCount('article_bookmarks', 0);
    }
}
