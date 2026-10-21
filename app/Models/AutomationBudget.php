<?php

namespace App\Models;

use Database\Factories\AutomationBudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'currency', 'monthly_limit_minor', 'reserved_minor', 'settled_minor', 'period_start', 'active_task_limit', 'daily_run_limit', 'concurrency_limit'])]
class AutomationBudget extends Model
{
    /** @use HasFactory<AutomationBudgetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['period_start' => 'date'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
