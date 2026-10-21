<?php

namespace Database\Factories;

use App\Models\AutomationQuote;
use App\Models\AutomationRun;
use App\Models\AutomationTask;
use App\Models\AutomationTaskVersion;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRun>
 */
class AutomationRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['automation_task_id' => AutomationTask::factory(), 'organization_id' => Organization::factory(), 'automation_task_version_id' => AutomationTaskVersion::factory(), 'automation_quote_id' => AutomationQuote::factory(), 'trigger' => 'MANUAL', 'status' => 'PENDIENTE', 'window_from' => now()->subDay(), 'window_to' => now(), 'idempotency_key' => 'test:'.fake()->uuid()];
    }
}
