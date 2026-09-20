<?php

namespace App\Models;

use App\Support\ReadingTime;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'source_id',
        'title',
        'description',
        'content',
        'author',
        'url',
        'image_url',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Estimated minutes to read this article, based on whichever is
     * more substantial: the full content, or the description as a
     * fallback for articles NewsAPI only gave us a summary for.
     */
    protected function readingTimeMinutes(): Attribute
    {
        return Attribute::make(
            get: fn () => ReadingTime::estimateMinutes($this->content ?? $this->description),
        )->shouldCache();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
