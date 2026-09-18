<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_an_empty_state_when_there_are_no_articles(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('No articles yet');
    }

    public function test_homepage_lists_latest_articles_with_category_and_source(): void
    {
        $category = Category::factory()->create(['name' => 'Technology']);
        $source = Source::factory()->create(['name' => 'The Verge']);

        Article::factory()->create([
            'category_id' => $category->id,
            'source_id' => $source->id,
            'title' => 'A Very Specific Headline',
            'published_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('A Very Specific Headline')
            ->assertSee('Technology')
            ->assertSee('The Verge');
    }

    public function test_homepage_paginates_articles(): void
    {
        Article::factory()->count(15)->create();

        $response = $this->get('/');

        $response->assertOk();
        $this->assertCount(12, $response->viewData('articles'));
        $this->assertSame(15, $response->viewData('articles')->total());
    }

    public function test_article_detail_page_shows_full_content(): void
    {
        $article = Article::factory()->create([
            'title' => 'A Detailed Article',
            'content' => 'The full article body.',
        ]);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('A Detailed Article')
            ->assertSee('The full article body.')
            ->assertSee($article->url);
    }

    public function test_visiting_a_nonexistent_article_returns_a_friendly_404(): void
    {
        $this->get('/articles/999999')
            ->assertNotFound()
            ->assertSee("We couldn't find that page", false);
    }
}
