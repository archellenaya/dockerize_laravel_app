<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms every route that changes or exposes per-user data actually
 * requires authentication - i.e. the "auth" middleware group in
 * routes/web.php hasn't been forgotten on a route, or removed by a
 * future edit.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_view_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_save_an_article(): void
    {
        $article = Article::factory()->create();

        $this->post(route('bookmarks.store', $article))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_unsave_an_article(): void
    {
        $article = Article::factory()->create();

        $this->delete(route('bookmarks.destroy', $article))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_follow_a_category(): void
    {
        $category = Category::factory()->create();

        $this->post(route('categories.follow', $category))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_unfollow_a_category(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('categories.unfollow', $category))->assertRedirect(route('login'));
    }

    public function test_none_of_the_guest_only_actions_leave_a_database_trace(): void
    {
        $article = Article::factory()->create();
        $category = Category::factory()->create();

        $this->post(route('bookmarks.store', $article));
        $this->post(route('categories.follow', $category));

        $this->assertDatabaseCount('article_bookmarks', 0);
        $this->assertDatabaseCount('category_follows', 0);
    }

    public function test_an_authenticated_user_is_kept_off_the_guest_only_login_and_register_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect();
        $this->actingAs($user)->get(route('register'))->assertRedirect();
    }
}
