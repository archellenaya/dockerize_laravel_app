<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Source;
use App\Repositories\Contracts\SourceRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EloquentSourceRepository implements SourceRepositoryInterface
{
    private const UNKNOWN_SOURCE_NAME = 'Unknown';

    public function firstOrCreateByName(string $name): Source
    {
        $name = $name !== '' ? $name : self::UNKNOWN_SOURCE_NAME;
        $slug = Str::slug($name) ?: Str::slug(self::UNKNOWN_SOURCE_NAME);

        return Source::firstOrCreate(['slug' => $slug], ['name' => $name]);
    }

    public function allWithArticles(): Collection
    {
        return Source::has('articles')->orderBy('name')->get();
    }
}
