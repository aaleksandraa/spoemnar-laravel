<?php

namespace Database\Factories;

use App\Models\Memorial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemorialVideo>
 */
class MemorialVideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'memorial_id' => Memorial::factory(),
            'youtube_url' => 'https://www.youtube.com/watch?v=' . fake()->regexify('[A-Za-z0-9_-]{11}'),
            'title' => fake()->sentence(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
