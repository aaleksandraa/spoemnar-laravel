<?php

namespace Database\Factories;

use App\Models\Memorial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemorialImage>
 */
class MemorialImageFactory extends Factory
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
            'image_url' => fake()->imageUrl(),
            'caption' => fake()->sentence(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}
