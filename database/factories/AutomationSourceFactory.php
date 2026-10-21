<?php

namespace Database\Factories;

use App\Models\AutomationResult;
use App\Models\AutomationSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationSource>
 */
class AutomationSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = fake()->unique()->url();

        return ['automation_result_id' => AutomationResult::factory(), 'title' => fake()->sentence(), 'url' => $url, 'canonical_url_hash' => hash('sha256', $url), 'publisher' => fake()->company(), 'published_at' => now()->subHour(), 'consulted_at' => now(), 'citation' => fake()->sentence(), 'publication_date_verified' => true];
    }
}
