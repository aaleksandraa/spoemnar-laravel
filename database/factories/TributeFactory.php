<?php

namespace Database\Factories;

use App\Models\Memorial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tribute>
 */
class TributeFactory extends Factory
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
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'message' => fake()->paragraph(),
        ];
    }
}
