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
}
