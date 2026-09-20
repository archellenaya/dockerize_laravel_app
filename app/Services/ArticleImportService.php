<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ArticlePublished;
use App\Exceptions\DuplicateArticleException;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SourceRepositoryInterface;
use App\Rules\HttpUrl;
use App\Rules\NotFutureDate;
use App\Services\Contracts\ArticleImportServiceInterface;
use App\Support\ArticleSanitizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Sanitizes, validates, and persists articles fetched from an external
 * news API, skipping duplicates and invalid records.
 *
 * This class owns the business rules of importing an article - what
 * counts as a duplicate, which fields are required, how a category or
 * source name maps to a record - and delegates all persistence to the
 * injected repositories, per the repository pattern. It depends only on
 * repository interfaces, never on Eloquent models, so it stays testable
 * and swappable in isolation.
 */
final class ArticleImportService implements ArticleImportServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly CategoryRepositoryInterface $categories,
        private readonly SourceRepositoryInterface $sources,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rawArticles
     * @return array{created: int, duplicates: int, invalid: int, errors: array<int, array{url: mixed, errors: array<int, string>}>}
     */
    public function importMany(array $rawArticles): array
    {
        $created = 0;
        $duplicates = 0;
        $invalid = 0;
        $errors = [];
        $seenUrls = [];

        foreach ($rawArticles as $raw) {
            $sanitized = ArticleSanitizer::sanitize($raw);

            if (blank($sanitized['url'])) {
                $invalid++;
                $errors[] = ['url' => $raw['url'] ?? null, 'errors' => ['The url is missing or malformed.']];

                continue;
            }

            if (isset($seenUrls[$sanitized['url']]) || $this->articles->existsByUrl($sanitized['url'])) {
                $duplicates++;

                continue;
            }

            $validator = Validator::make($sanitized, $this->rules());

            if ($validator->fails()) {
                $invalid++;
                $errors[] = ['url' => $sanitized['url'], 'errors' => $validator->errors()->all()];
                Log::warning('Skipped invalid article during import.', [
                    'url' => $sanitized['url'],
                    'errors' => $validator->errors()->all(),
                ]);

                continue;
            }

            $data = $validator->validated();
            $seenUrls[$data['url']] = true;

            try {
                $article = $this->articles->create([
                    'category_id' => $this->resolveCategoryId($data['category_name'] ?? null),
                    'source_id' => $this->resolveSourceId($data['source_name']),
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'content' => $data['content'] ?? null,
                    'author' => $data['author'] ?? null,
                    'url' => $data['url'],
                    'image_url' => $data['image_url'] ?? null,
                    'published_at' => $data['published_at'] ?? null,
                ]);
            } catch (DuplicateArticleException) {
                // Lost a race with another process importing the same URL.
                $duplicates++;

                continue;
            }

            ArticlePublished::dispatch($article);

            $created++;
        }

        return compact('created', 'duplicates', 'invalid', 'errors');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255', new HttpUrl],
            'image_url' => ['nullable', 'string', 'max:255', new HttpUrl],
            'published_at' => ['nullable', 'date', new NotFutureDate],
            'source_name' => ['required', 'string', 'max:255'],
            'category_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Null means "no usable category name was supplied" - not every
     * article carries one, and the schema allows that (see
     * database/migrations/*_create_articles_table.php).
     */
    private function resolveCategoryId(?string $name): ?int
    {
        if (blank($name) || Str::slug($name) === '') {
            return null;
        }

        return $this->categories->firstOrCreateByName($name)->id;
    }

    private function resolveSourceId(string $name): int
    {
        return $this->sources->firstOrCreateByName($name)->id;
    }
}
