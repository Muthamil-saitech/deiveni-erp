<?php

namespace Database\Factories;

use App\Models\WorkerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerCategory>
 */
class WorkerCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ta' => fake()->randomElement(['பிளம்பர்', 'மின்சார பணியாளர்', 'கட்டிட தொழிலாளி', 'கார்பெண்டர்']),
            'name_en' => fake()->randomElement(['Plumber', 'Electrician', 'Mason Helper', 'Carpenter']),
            'code' => sprintf('CAT-%03d', fake()->unique()->numberBetween(100, 999)),
            'daily_wage' => fake()->randomFloat(2, 700, 1800),
            'shift' => '8 Hours (8:30 AM - 5:30 PM)',
            'status' => 'Active',
        ];
    }
}
