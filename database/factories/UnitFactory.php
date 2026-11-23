<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->unique()->word(),
            'name_ta' => fake()->randomElement(['அலகு', 'பெட்டி', 'மீட்டர்', 'பண்டில்']),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'usage' => fake()->sentence(3),
            'status' => 'Active',
        ];
    }
}
