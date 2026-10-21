<?php

namespace App\Services\Automation;

use App\Models\AutomationQuote;
use App\Models\AutomationRate;
use App\Models\AutomationTaskVersion;
use Carbon\CarbonImmutable;

class AutomationQuoteService
{
    public function __construct(private readonly AutomationSchedule $schedule) {}

    public function quote(AutomationTaskVersion $version): AutomationQuote
    {
        $version->loadMissing('task');
        $rate = AutomationRate::query()
            ->where('task_type', $version->task->type)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->latest('version')->first();

        if (! $rate) {
            throw new \DomainException('No existe una tarifa vigente para este servicio.');
        }

        $occurrences = $this->schedule->projectedOccurrences($version->configuration, CarbonImmutable::now('UTC'));

        return $version->task->quotes()->create([
            'automation_task_version_id' => $version->id,
            'organization_id' => $version->task->organization_id,
            'automation_rate_id' => $rate->id,
            'price_per_run_minor' => $rate->price_per_run_minor,
            'projected_occurrences' => $occurrences,
            'projected_monthly_minor' => $rate->price_per_run_minor * $occurrences,
            'currency' => $rate->currency,
            'commercial_policy' => $rate->policies,
            'valid_until' => now()->addDays(config('services.automation.quote_valid_days', 7)),
        ]);
    }
}
