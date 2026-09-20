<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Article;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when ArticleImportService persists a new article. Deliberately
 * carries no notification logic itself - that's NotifyCategoryFollowersOfNewArticle's
 * job - so the import pipeline stays decoupled from "what happens after".
 */
class ArticlePublished
{
    use Dispatchable;

    public function __construct(
        public readonly Article $article,
    ) {}
}
