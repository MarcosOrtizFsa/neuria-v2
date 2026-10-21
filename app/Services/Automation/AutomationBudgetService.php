<?php

namespace App\Services\Automation;

use App\Models\AutomationBudget;
use App\Models\AutomationLedgerEntry;
use App\Models\AutomationRun;
use Illuminate\Support\Facades\DB;

class AutomationBudgetService
{
    public function reserve(AutomationRun $run): bool
    {
        return DB::transaction(function () use ($run): bool {
            $budget = AutomationBudget::query()->where('organization_id', $run->organization_id)->lockForUpdate()->first();
            if (! $budget || $budget->currency !== $run->quote->currency) {
                return false;
            }
            $this->resetPeriodIfNeeded($budget);
            $amount = $run->quote->price_per_run_minor;
            if ($budget->reserved_minor + $budget->settled_minor + $amount > $budget->monthly_limit_minor) {
                return false;
            }
            $running = AutomationRun::query()->where('organization_id', $run->organization_id)->where('status', 'PROCESANDO')->lockForUpdate()->count();
            if ($running > $budget->concurrency_limit) {
                return false;
            }
            $daily = AutomationRun::query()->where('organization_id', $run->organization_id)->whereDate('created_at', today())->whereNotIn('status', ['OMITIDA', 'CANCELADA'])->count();
            if ($daily > $budget->daily_run_limit) {
                return false;
            }
            $entry = AutomationLedgerEntry::firstOrCreate(['automation_run_id' => $run->id, 'type' => 'RESERVA'], ['organization_id' => $run->organization_id, 'automation_quote_id' => $run->automation_quote_id, 'amount_minor' => $amount, 'currency' => $run->quote->currency, 'reason' => 'Reserva previa a la ejecución']);
            if ($entry->wasRecentlyCreated) {
                $budget->increment('reserved_minor', $amount);
            }

            return true;
        });
    }

    public function settle(AutomationRun $run): void
    {
        DB::transaction(function () use ($run): void {
            $budget = AutomationBudget::query()->where('organization_id', $run->organization_id)->lockForUpdate()->firstOrFail();
            $amount = $run->quote->price_per_run_minor;
            $entry = AutomationLedgerEntry::firstOrCreate(['automation_run_id' => $run->id, 'type' => 'LIQUIDACION'], ['organization_id' => $run->organization_id, 'automation_quote_id' => $run->automation_quote_id, 'amount_minor' => $amount, 'currency' => $run->quote->currency, 'reason' => 'Resultado válido']);
            if ($entry->wasRecentlyCreated) {
                $budget->update(['reserved_minor' => max(0, $budget->reserved_minor - $amount), 'settled_minor' => $budget->settled_minor + $amount]);
            }
        });
    }

    public function release(AutomationRun $run, string $reason): void
    {
        DB::transaction(function () use ($run, $reason): void {
            $budget = AutomationBudget::query()->where('organization_id', $run->organization_id)->lockForUpdate()->first();
            if (! $budget) {
                return;
            }
            $amount = $run->quote->price_per_run_minor;
            $entry = AutomationLedgerEntry::firstOrCreate(['automation_run_id' => $run->id, 'type' => 'LIBERACION'], ['organization_id' => $run->organization_id, 'automation_quote_id' => $run->automation_quote_id, 'amount_minor' => $amount, 'currency' => $run->quote->currency, 'reason' => $reason]);
            if ($entry->wasRecentlyCreated && $run->ledgerEntries()->where('type', 'RESERVA')->exists()) {
                $budget->update(['reserved_minor' => max(0, $budget->reserved_minor - $amount)]);
            }
        });
    }

    private function resetPeriodIfNeeded(AutomationBudget $budget): void
    {
        if (! $budget->period_start->isSameMonth(now())) {
            $budget->update(['period_start' => now()->startOfMonth()->toDateString(), 'reserved_minor' => 0, 'settled_minor' => 0]);
            $budget->refresh();
        }
    }
}
