<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bookmark/follow routes are keyed by the target resource
 * (article/category), not by the row a user is trying to affect - e.g.
 * DELETE /articles/{article}/bookmark, not DELETE /bookmarks/{id}. The
 * delete/exists queries are always scoped to `where user_id = auth()->id()`,
 * so there is no ID an attacker can supply that reaches another user's
 * row. These tests prove that by having Bob target exactly the same
 * article/category Alice already saved/followed, and confirming Alice's
 * row survives.
 */
class IdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_delete_another_users_bookmark_via_the_same_article_id(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($alice)->post(route('bookmarks.store', $article));
        $this->assertDatabaseHas('article_bookmarks', ['user_id' => $alice->id, 'article_id' => $article->id]);

        // Bob never bookmarked this article, but tries to "unbookmark" it anyway.
        $this->actingAs($bob)->delete(route('bookmarks.destroy', $article))->assertRedirect();

        // Alice's bookmark must still exist - Bob's delete only ever
        // could have matched a (bob, $article) row, which never existed.
        $this->assertDatabaseHas('article_bookmarks', ['user_id' => $alice->id, 'article_id' => $article->id]);
        $this->assertDatabaseCount('article_bookmarks', 1);
    }

    public function test_a_user_cannot_delete_another_users_category_follow_via_the_same_category_id(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($alice)->post(route('categories.follow', $category));
        $this->assertDatabaseHas('category_follows', ['user_id' => $alice->id, 'category_id' => $category->id]);

        $this->actingAs($bob)->delete(route('categories.unfollow', $category))->assertRedirect();

        $this->assertDatabaseHas('category_follows', ['user_id' => $alice->id, 'category_id' => $category->id]);
        $this->assertDatabaseCount('category_follows', 1);
    }

    public function test_a_users_dashboard_never_shows_another_users_saved_articles(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $alicesArticle = Article::factory()->create(['title' => "Alice's Private Save"]);
        $bobsArticle = Article::factory()->create(['title' => "Bob's Private Save"]);

        $this->actingAs($alice)->post(route('bookmarks.store', $alicesArticle));
        $this->actingAs($bob)->post(route('bookmarks.store', $bobsArticle));

        $this->actingAs($bob)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Bob's Private Save")
            ->assertDontSee("Alice's Private Save");
    }
}
