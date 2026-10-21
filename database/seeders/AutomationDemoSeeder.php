<?php

namespace Database\Seeders;

use App\Models\AutomationBudget;
use App\Models\AutomationRate;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class AutomationDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AutomationRate::updateOrCreate(['code' => 'NEWS_STANDARD_DEMO', 'version' => 1], [
            'name' => 'Investigación y nota — tarifa demostrativa', 'task_type' => 'NEWS_RESEARCH_AND_WRITE',
            'price_per_run_minor' => 100000, 'currency' => 'ARS',
            'policies' => ['charge_valid_result' => true, 'charge_no_news' => false, 'charge_invalid' => false, 'interpretation_included' => true, 'maximum_correction_attempts' => 1],
            'valid_from' => now()->startOfDay(), 'is_demo' => true, 'is_active' => true,
        ]);
        Organization::query()->where('is_active', true)->each(function (Organization $organization): void {
            AutomationBudget::firstOrCreate(['organization_id' => $organization->id], ['currency' => 'ARS', 'monthly_limit_minor' => 10000000, 'period_start' => now()->startOfMonth(), 'active_task_limit' => 10, 'daily_run_limit' => 20, 'concurrency_limit' => 2]);
        });
    }
}
