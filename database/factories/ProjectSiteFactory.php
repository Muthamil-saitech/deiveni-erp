<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ProjectSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectSite>
 */
class ProjectSiteFactory extends Factory
{
    protected $model = ProjectSite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'name' => fake()->randomElement(['Site A - Duplex Villa', 'Site B - Commercial Complex', 'Site C - Residential Flat', 'Site D - Renovation']),
            'location' => fake()->randomElement(['Porur, Chennai', 'Tambaram, Chennai', 'Anna Nagar, Chennai', 'Guindy, Chennai']),
            'work_type' => fake()->randomElement(['New Build', 'Alteration']),
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'estimated_budget' => fake()->randomElement([2500000, 3500000, 5000000, 7500000, 10000000]),
            'spent' => 0.00,
            'status' => fake()->randomElement(['In Progress', 'Planning', 'Completed', 'On Hold']),
        ];
    }
}
