<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Mail\NewArticleInCategoryMail;
use App\Services\Contracts\CategoryFollowServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Emails everyone following an article's category when it's imported.
 * Queued (see ShouldQueue) so a slow mail transport can never delay the
 * news:fetch command itself - with QUEUE_CONNECTION=sync in this app's
 * .env, that's still synchronous today, but the contract is in place for
 * when it isn't.
 */
class NotifyCategoryFollowersOfNewArticle implements ShouldQueue
{
    public function __construct(
        private readonly CategoryFollowServiceInterface $follows,
    ) {}

    public function handle(ArticlePublished $event): void
    {
        $article = $event->article;

        if ($article->category === null) {
            return;
        }

        $followers = $this->follows->followersOf($article->category);

        foreach ($followers as $follower) {
            Mail::to($follower->email)->send(new NewArticleInCategoryMail($article));
        }
    }
}
