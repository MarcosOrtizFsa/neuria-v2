<?php

namespace Database\Factories;

use App\Models\MediaOutlet;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaOutlet>
 */
class MediaOutletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'name' => fake()->company(), 'trade_name' => fake()->companySuffix(), 'public_identifier' => fake()->unique()->slug(), 'main_url' => fake()->url(), 'description' => fake()->sentence(), 'contact_email' => fake()->companyEmail(), 'contact_phone' => fake()->phoneNumber(), 'type' => 'PORTAL_NOTICIAS', 'status' => 'ACTIVO',
        ];
    }
}
