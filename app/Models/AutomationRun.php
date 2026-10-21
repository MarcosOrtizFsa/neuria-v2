<?php

namespace App\Models;

use Database\Factories\AutomationRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'automation_task_id', 'organization_id', 'automation_task_version_id', 'automation_quote_id', 'scheduled_for', 'trigger', 'status', 'attempts', 'dispatched_at', 'started_at', 'finished_at', 'window_from', 'window_to', 'provider_request_id', 'provider_response_id', 'sanitized_error', 'idempotency_key'])]
class AutomationRun extends Model
{
    /** @use HasFactory<AutomationRunFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (self $run) => $run->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'dispatched_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'window_from' => 'datetime', 'window_to' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AutomationTask::class, 'automation_task_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AutomationTaskVersion::class, 'automation_task_version_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(AutomationQuote::class, 'automation_quote_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(AutomationResult::class);
    }

    public function usageAttempts(): HasMany
    {
        return $this->hasMany(AutomationUsageAttempt::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AutomationLedgerEntry::class);
    }
}
