<?php

namespace Database\Factories;

use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaChannel>
 */
class MediaChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'media_outlet_id' => MediaOutlet::factory(), 'name' => 'Web', 'type' => 'WEB', 'url' => fake()->url(), 'status' => 'ACTIVO', 'editorial_overrides' => [],
        ];
    }
}
