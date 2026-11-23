<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_id' => Bank::inRandomOrder()->value('id') ?? Bank::factory(),
            'branch_name' => fake()->city(),
            'branch_name_ta' => null,
            'ifsc_code' => strtoupper(fake()->lexify('????')).'0'.fake()->numerify('######'),
            'contact_person' => fake()->name(),
            'phone' => '+91 '.fake()->numerify('##### #####'),
            'valuation_fee' => fake()->randomElement([5000, 6000, 7500, 8000, 10000]),
            'status' => fake()->randomElement(['Active', 'Inactive']),
        ];
    }
}
