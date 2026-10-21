<?php

namespace Database\Factories;

use App\Models\AutomationTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationTask>
 */
class AutomationTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'created_by' => User::factory(), 'name' => fake()->sentence(4), 'type' => 'NEWS_RESEARCH_AND_WRITE', 'status' => 'PENDIENTE_CONFIRMACION', 'current_version' => 1, 'timezone' => 'America/Argentina/Buenos_Aires'];
    }
}
