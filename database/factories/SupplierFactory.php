<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('9#########'),
            'address' => fake()->address(),
            'gst_number' => fake()->bothify('33#####????#Z#'),
            'status' => 'Active',
            'is_site_based' => true,
        ];
    }
}
