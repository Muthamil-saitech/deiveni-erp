<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'shop_home_office_name' => 'Shop '.fake()->numberBetween(1, 20),
            'property_type' => fake()->randomElement(['Commercial Shop', 'Residential Flat', 'Office Space', 'Warehouse / Shed']),
            'location' => fake()->streetAddress().', Chennai',
            'tenant_name' => fake()->name(),
            'tenant_phone' => '+91 '.fake()->numerify('9840# #####'),
            'monthly_rent' => fake()->numberBetween(5000, 50000),
            'security_deposit' => fake()->numberBetween(25000, 300000),
            'rent_due_day' => fake()->randomElement(['1st of Month', '5th of Month', '7th of Month', '10th of Month', '15th of Month']),
            'status' => 'Active',
        ];
    }
}
