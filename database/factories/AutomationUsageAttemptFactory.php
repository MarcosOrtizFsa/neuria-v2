<?php

namespace Database\Factories;

use App\Models\AutomationRun;
use App\Models\AutomationUsageAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationUsageAttempt>
 */
class AutomationUsageAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['automation_run_id' => AutomationRun::factory(), 'attempt' => 1, 'operation' => 'INVESTIGACION_Y_REDACCION', 'model' => 'gpt-test', 'input_tokens' => 100, 'output_tokens' => 200, 'total_tokens' => 300, 'tool_calls' => 1, 'cost_condition' => 'NO_CONCILIADA', 'status' => 'COMPLETADA'];
    }
}
