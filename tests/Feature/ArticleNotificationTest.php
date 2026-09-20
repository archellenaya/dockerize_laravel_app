<?php

namespace Tests\Feature;

use App\Mail\NewArticleInCategoryMail;
use App\Models\Category;
use App\Models\User;
use App\Services\Contracts\ArticleImportServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ArticleNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_importing_an_article_emails_followers_of_its_category(): void
    {
        Mail::fake();

        $follower = User::factory()->create();
        $nonFollower = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

        $this->actingAs($follower)->post(route('categories.follow', $category));

        app(ArticleImportServiceInterface::class)->importMany([[
            'title' => 'A Followed Category Headline',
            'source' => 'Test Source',
            'category' => 'Technology',
            'url' => 'https://example.com/followed-category-headline',
            'published_at' => now()->toIso8601String(),
        ]]);

        Mail::assertSent(NewArticleInCategoryMail::class, function (NewArticleInCategoryMail $mail) use ($follower) {
            return $mail->hasTo($follower->email);
        });

        Mail::assertNotSent(NewArticleInCategoryMail::class, function (NewArticleInCategoryMail $mail) use ($nonFollower) {
            return $mail->hasTo($nonFollower->email);
        });
    }

    public function test_importing_an_uncategorized_article_sends_no_email(): void
    {
        Mail::fake();

        app(ArticleImportServiceInterface::class)->importMany([[
            'title' => 'No Category Headline',
            'source' => 'Test Source',
            'category' => null,
            'url' => 'https://example.com/no-category-headline',
            'published_at' => now()->toIso8601String(),
        ]]);

        Mail::assertNothingSent();
    }
}
