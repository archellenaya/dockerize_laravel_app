<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'source_id' => Source::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'content' => $this->faker->optional()->paragraphs(3, true),
            'author' => $this->faker->optional()->name(),
            'url' => $this->faker->unique()->url(),
            'image_url' => $this->faker->optional()->imageUrl(),
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
