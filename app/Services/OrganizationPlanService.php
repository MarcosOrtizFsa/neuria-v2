<?php

namespace App\Services;

use App\Models\AutomationBudget;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class OrganizationPlanService
{
    /**
     * @param  array{currency: string, monthly_limit_minor: int, active_task_limit: int, daily_run_limit: int, concurrency_limit: int}  $attributes
     */
    public function update(Organization $organization, array $attributes): AutomationBudget
    {
        return DB::transaction(function () use ($organization, $attributes): AutomationBudget {
            $budget = AutomationBudget::query()
                ->whereBelongsTo($organization)
                ->lockForUpdate()
                ->first();

            if ($budget === null) {
                return AutomationBudget::create([
                    'organization_id' => $organization->id,
                    ...$attributes,
                    'reserved_minor' => 0,
                    'settled_minor' => 0,
                    'period_start' => now()->startOfMonth()->toDateString(),
                ]);
            }

            if (! $budget->period_start->isSameMonth(now())) {
                $attributes['reserved_minor'] = 0;
                $attributes['settled_minor'] = 0;
                $attributes['period_start'] = now()->startOfMonth()->toDateString();
            }

            $budget->update($attributes);

            return $budget->refresh();
        });
    }
}
