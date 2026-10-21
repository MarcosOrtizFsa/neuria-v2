<?php

namespace Database\Factories;

use App\Models\AutomationLedgerEntry;
use App\Models\AutomationQuote;
use App\Models\AutomationRun;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationLedgerEntry>
 */
class AutomationLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'automation_run_id' => AutomationRun::factory(), 'automation_quote_id' => AutomationQuote::factory(), 'type' => 'RESERVA', 'amount_minor' => 100000, 'currency' => 'ARS', 'reason' => 'Reserva de prueba'];
    }
}
