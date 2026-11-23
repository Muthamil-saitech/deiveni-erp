<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->words(2, true),
            'name_ta' => 'கட்டுமான பொருள்',
            'unit_id' => Unit::query()->inRandomOrder()->value('id') ?? Unit::factory(),
            'status' => 'Active',
        ];
    }
}
