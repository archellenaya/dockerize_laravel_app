<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Category;

interface CategoryRepositoryInterface
{
    /**
     * Find the category matching this name, or create it. Callers pass a
     * human-readable name (e.g. "Technology") and never need to know how
     * categories are keyed internally (e.g. by slug).
     */
    public function firstOrCreateByName(string $name): Category;
}
