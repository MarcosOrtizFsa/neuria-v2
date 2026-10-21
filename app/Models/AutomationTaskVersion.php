<?php

namespace App\Models;

use Database\Factories\AutomationTaskVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['automation_task_id', 'created_by', 'version', 'configuration', 'configuration_hash', 'change_note', 'accepted_at'])]
class AutomationTaskVersion extends Model
{
    /** @use HasFactory<AutomationTaskVersionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['configuration' => 'array', 'accepted_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AutomationTask::class, 'automation_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(AutomationQuote::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
