<?php

namespace Database\Factories;

use App\Models\AutomationQuote;
use App\Models\AutomationRate;
use App\Models\AutomationTask;
use App\Models\AutomationTaskVersion;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationQuote>
 */
class AutomationQuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['automation_task_id' => AutomationTask::factory(), 'automation_task_version_id' => AutomationTaskVersion::factory(), 'organization_id' => Organization::factory(), 'automation_rate_id' => AutomationRate::factory(), 'price_per_run_minor' => 100000, 'projected_occurrences' => 20, 'projected_monthly_minor' => 2000000, 'currency' => 'ARS', 'commercial_policy' => ['charge_valid_result' => true, 'charge_no_news' => false], 'valid_until' => now()->addWeek()];
    }
}
