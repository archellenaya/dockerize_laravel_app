<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Rules\HttpUrl;
use App\Rules\NotFutureDate;
use App\Support\ArticleSanitizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Sanitizes, validates, and persists articles fetched from an external
 * news API, skipping duplicates and invalid records.
 */
class ArticleImportService
{
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

            if (isset($seenUrls[$sanitized['url']]) || Article::where('url', $sanitized['url'])->exists()) {
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
                $article = Article::create([
                    'category_id' => $this->resolveCategory($data['category_name'] ?? null)?->id,
                    'source_id' => $this->resolveSource($data['source_name'])->id,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'content' => $data['content'] ?? null,
                    'author' => $data['author'] ?? null,
                    'url' => $data['url'],
                    'image_url' => $data['image_url'] ?? null,
                    'published_at' => $data['published_at'] ?? null,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Lost a race with another process importing the same URL.
                $duplicates++;

                continue;
            }

            $created++;
            unset($article);
        }

        return compact('created', 'duplicates', 'invalid', 'errors');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
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

    protected function resolveCategory(?string $name): ?Category
    {
        if (blank($name)) {
            return null;
        }

        $slug = Str::slug($name);

        if ($slug === '') {
            return null;
        }

        return Category::firstOrCreate(['slug' => $slug], ['name' => Str::title($name)]);
    }

    protected function resolveSource(string $name): Source
    {
        $slug = Str::slug($name) ?: 'unknown';

        return Source::firstOrCreate(['slug' => $slug], ['name' => $name ?: 'Unknown']);
    }
}
