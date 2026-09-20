<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Find the category matching this name, or create it. Callers pass a
     * human-readable name (e.g. "Technology") and never need to know how
     * categories are keyed internally (e.g. by slug).
     */
    public function firstOrCreateByName(string $name): Category;

    /**
     * Categories that have at least one article, alphabetically - meant
     * for populating a filter control, where an empty category would
     * just be a dead end.
     *
     * @return Collection<int, Category>
     */
    public function allWithArticles(): Collection;
}
