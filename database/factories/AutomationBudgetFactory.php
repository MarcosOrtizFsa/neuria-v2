<?php

namespace Database\Factories;

use App\Models\AutomationBudget;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationBudget>
 */
class AutomationBudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'currency' => 'ARS', 'monthly_limit_minor' => 10000000, 'period_start' => now()->startOfMonth(), 'active_task_limit' => 10, 'daily_run_limit' => 20, 'concurrency_limit' => 2];
    }
}
