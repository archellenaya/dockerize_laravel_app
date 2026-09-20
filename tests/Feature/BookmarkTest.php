<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login_when_saving_an_article(): void
    {
        $article = Article::factory()->create();

        $response = $this->post(route('bookmarks.store', $article));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('article_bookmarks', 0);
    }

    public function test_an_authenticated_user_can_save_an_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $response = $this->actingAs($user)->post(route('bookmarks.store', $article));

        $response->assertRedirect();
        $this->assertDatabaseHas('article_bookmarks', [
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_saving_the_same_article_twice_does_not_create_a_duplicate_row(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->post(route('bookmarks.store', $article));
        $this->actingAs($user)->post(route('bookmarks.store', $article));

        $this->assertDatabaseCount('article_bookmarks', 1);
    }

    public function test_an_authenticated_user_can_unsave_an_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)->post(route('bookmarks.store', $article));
        $response = $this->actingAs($user)->delete(route('bookmarks.destroy', $article));

        $response->assertRedirect();
        $this->assertDatabaseCount('article_bookmarks', 0);
    }

    public function test_the_dashboard_only_shows_the_current_users_saved_articles(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $mine = Article::factory()->create(['title' => 'My Saved Article']);
        $theirs = Article::factory()->create(['title' => 'Their Saved Article']);

        $this->actingAs($user)->post(route('bookmarks.store', $mine));
        $this->actingAs($otherUser)->post(route('bookmarks.store', $theirs));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Saved Article')
            ->assertDontSee('Their Saved Article');
    }

    public function test_the_dashboard_shows_an_empty_state_with_no_saved_articles(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("You haven't saved any articles yet", false);
    }

    public function test_a_guest_cannot_view_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_homepage_shows_the_saved_state_for_a_bookmarked_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['title' => 'A Saved Headline']);

        $this->actingAs($user)->post(route('bookmarks.store', $article));

        $this->actingAs($user)
            ->get(route('articles.index'))
            ->assertOk()
            ->assertSee('Saved');
    }
}
