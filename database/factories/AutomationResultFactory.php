<?php

namespace Database\Factories;

use App\Models\AutomationResult;
use App\Models\AutomationRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationResult>
 */
class AutomationResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['automation_run_id' => AutomationRun::factory(), 'status' => 'VALIDO', 'title' => fake()->sentence(), 'body' => fake()->text(800), 'character_count' => 800, 'warnings' => []];
    }
}
