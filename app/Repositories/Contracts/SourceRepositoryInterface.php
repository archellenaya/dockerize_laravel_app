<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Source;
use Illuminate\Support\Collection;

interface SourceRepositoryInterface
{
    /**
     * Find the source matching this name, or create it. An empty or
     * unrecognized name resolves to a shared "Unknown" source rather
     * than failing, since NewsAPI does not always report one.
     */
    public function firstOrCreateByName(string $name): Source;

    /**
     * Sources that have at least one article, alphabetically - meant for
     * populating a filter control, where an empty source would just be a
     * dead end.
     *
     * @return Collection<int, Source>
     */
    public function allWithArticles(): Collection;
}
