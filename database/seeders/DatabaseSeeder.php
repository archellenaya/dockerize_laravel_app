<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            CategorySeeder::class,
            SourceSeeder::class,
        ]);

        Article::factory()
            ->count(20)
            ->create([
                'category_id' => fn () => Category::inRandomOrder()->value('id'),
                'source_id' => fn () => Source::inRandomOrder()->value('id'),
            ]);
    }
}
