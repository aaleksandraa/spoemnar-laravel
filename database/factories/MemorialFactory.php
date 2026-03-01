<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Memorial>
 */
class MemorialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'user_id' => Profile::factory()->create()->user_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => fake()->date('Y-m-d', '-30 years'),
            'death_date' => fake()->date('Y-m-d', '-1 year'),
            'birth_place' => fake()->city(),
            'death_place' => fake()->city(),
            'biography' => fake()->paragraph(),
            'profile_image_url' => fake()->imageUrl(),
            'slug' => \App\Models\Memorial::generateSlug($firstName, $lastName),
            'is_public' => fake()->boolean(),
        ];
    }
}
