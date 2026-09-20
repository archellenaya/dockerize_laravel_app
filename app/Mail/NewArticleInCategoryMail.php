<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Not itself queued - the listener that sends this (NotifyCategoryFollowersOfNewArticle)
 * is already a queued job, so queuing this too would double-defer it.
 */
class NewArticleInCategoryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Article $article,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New in {$this->article->category?->name}: {$this->article->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-article-in-category',
        );
    }
}
