<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login_when_following_a_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->post(route('categories.follow', $category));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('category_follows', 0);
    }

    public function test_an_authenticated_user_can_follow_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.follow', $category));

        $response->assertRedirect();
        $this->assertDatabaseHas('category_follows', [
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_following_the_same_category_twice_does_not_create_a_duplicate_row(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post(route('categories.follow', $category));
        $this->actingAs($user)->post(route('categories.follow', $category));

        $this->assertDatabaseCount('category_follows', 1);
    }

    public function test_an_authenticated_user_can_unfollow_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post(route('categories.follow', $category));
        $response = $this->actingAs($user)->delete(route('categories.unfollow', $category));

        $response->assertRedirect();
        $this->assertDatabaseCount('category_follows', 0);
    }

    public function test_the_dashboard_shows_which_categories_the_user_follows(): void
    {
        $user = User::factory()->create();
        $followed = Category::factory()->create(['name' => 'Followed Category']);
        $notFollowed = Category::factory()->create(['name' => 'Not Followed Category']);

        // A category only shows up as a filter option once it has an
        // article, per ArticleServiceInterface::availableCategories().
        Article::factory()->create(['category_id' => $followed->id]);
        Article::factory()->create(['category_id' => $notFollowed->id]);

        $this->actingAs($user)->post(route('categories.follow', $followed));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        // The followed category renders an "unfollow" (×) button; the
        // other renders a "+" follow button - assert both states appear.
        $response->assertSeeTextInOrder(['Followed Category', '×']);
        $response->assertSeeTextInOrder(['+', 'Not Followed Category']);
    }
}
