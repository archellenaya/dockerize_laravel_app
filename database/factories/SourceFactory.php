<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SourceFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'url' => $this->faker->url(),
            'description' => $this->faker->optional()->sentence(),
            'language' => 'en',
            'country' => $this->faker->countryCode(),
        ];
    }
}
