<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
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
            'trade_name' => fake()->companySuffix(),
            'contact_email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
            'internal_notes' => fake()->optional()->sentence(),
        ];
    }
}
