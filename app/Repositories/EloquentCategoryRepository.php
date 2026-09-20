<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Str;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function firstOrCreateByName(string $name): Category
    {
        $slug = Str::slug($name);

        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::title($name)],
        );
    }
}
