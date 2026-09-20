<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Immutable set of optional article listing filters. A null field means
 * "no constraint" - the caller (a Livewire component, a future API
 * endpoint, etc.) only fills in what the user actually chose.
 */
final class ArticleFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $categorySlug = null,
        public readonly ?string $sourceSlug = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->search === null
            && $this->categorySlug === null
            && $this->sourceSlug === null
            && $this->from === null
            && $this->to === null;
    }

    /**
     * Build from a plain query-string array, using the same key names
     * ArticleList's #[Url]-bound properties produce (search is aliased
     * to "q" there, so it is here too - this keeps a "current filters"
     * link, like an export button, consistent with the browser's URL).
     *
     * @param  array<string, mixed>  $query
     */
    public static function fromQuery(array $query): self
    {
        $clean = fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null;

        return new self(
            search: $clean($query['q'] ?? null),
            categorySlug: $clean($query['category'] ?? null),
            sourceSlug: $clean($query['source'] ?? null),
            from: $clean($query['from'] ?? null),
            to: $clean($query['to'] ?? null),
        );
    }
}
