<?php

namespace App\Models;

use Database\Factories\AutomationQuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'automation_task_id', 'automation_task_version_id', 'organization_id', 'automation_rate_id', 'price_per_run_minor', 'projected_occurrences', 'projected_monthly_minor', 'currency', 'commercial_policy', 'valid_until', 'accepted_at'])]
class AutomationQuote extends Model
{
    /** @use HasFactory<AutomationQuoteFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (self $quote) => $quote->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['commercial_policy' => 'array', 'valid_until' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AutomationTask::class, 'automation_task_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AutomationTaskVersion::class, 'automation_task_version_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(AutomationRate::class, 'automation_rate_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
