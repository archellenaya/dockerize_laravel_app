<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    /**
     * A handful of well-known NewsAPI sources, keyed by their NewsAPI source id.
     *
     * @var array<string, array<string, string>>
     */
    protected array $sources = [
        'bbc-news' => ['name' => 'BBC News', 'url' => 'https://www.bbc.co.uk/news', 'country' => 'gb'],
        'cnn' => ['name' => 'CNN', 'url' => 'https://www.cnn.com', 'country' => 'us'],
        'reuters' => ['name' => 'Reuters', 'url' => 'https://www.reuters.com', 'country' => 'us'],
        'the-verge' => ['name' => 'The Verge', 'url' => 'https://www.theverge.com', 'country' => 'us'],
        'techcrunch' => ['name' => 'TechCrunch', 'url' => 'https://techcrunch.com', 'country' => 'us'],
        'espn' => ['name' => 'ESPN', 'url' => 'https://www.espn.com', 'country' => 'us'],
        'bloomberg' => ['name' => 'Bloomberg', 'url' => 'https://www.bloomberg.com', 'country' => 'us'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ($this->sources as $slug => $attributes) {
            Source::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $attributes['name'],
                    'url' => $attributes['url'],
                    'country' => $attributes['country'],
                    'language' => 'en',
                ]
            );
        }
    }
}
