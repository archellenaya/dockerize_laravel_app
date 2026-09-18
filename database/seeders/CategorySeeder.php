<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * The NewsAPI top-headlines categories.
     *
     * @var array<int, string>
     */
    protected array $categories = [
        'Business',
        'Entertainment',
        'General',
        'Health',
        'Science',
        'Sports',
        'Technology',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ($this->categories as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
