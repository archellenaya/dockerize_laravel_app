<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Source;

interface SourceRepositoryInterface
{
    /**
     * Find the source matching this name, or create it. An empty or
     * unrecognized name resolves to a shared "Unknown" source rather
     * than failing, since NewsAPI does not always report one.
     */
    public function firstOrCreateByName(string $name): Source;
}
