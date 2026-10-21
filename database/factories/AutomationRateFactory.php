<?php

namespace Database\Factories;

use App\Models\AutomationRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRate>
 */
class AutomationRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['code' => 'NEWS_STANDARD', 'version' => 1, 'name' => 'Tarifa de prueba', 'task_type' => 'NEWS_RESEARCH_AND_WRITE', 'price_per_run_minor' => 100000, 'currency' => 'ARS', 'policies' => ['charge_valid_result' => true, 'charge_no_news' => false, 'charge_invalid' => false], 'valid_from' => now()->subDay(), 'is_demo' => true, 'is_active' => true];
    }
}
