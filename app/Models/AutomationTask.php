<?php

namespace App\Models;

use Database\Factories\AutomationTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'organization_id', 'created_by', 'name', 'type', 'status', 'current_version', 'original_request', 'timezone', 'next_run_at', 'confirmed_at', 'paused_at', 'archived_at'])]
class AutomationTask extends Model
{
    /** @use HasFactory<AutomationTaskFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (self $task) => $task->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['next_run_at' => 'datetime', 'confirmed_at' => 'datetime', 'paused_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AutomationTaskVersion::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(AutomationQuote::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function currentVersion(): ?AutomationTaskVersion
    {
        return $this->versions()->where('version', $this->current_version)->first();
    }
}
