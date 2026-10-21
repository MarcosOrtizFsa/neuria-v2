<?php

namespace App\Models;

use Database\Factories\AutomationResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['automation_run_id', 'status', 'title', 'body', 'character_count', 'warnings', 'provider_metadata'])]
class AutomationResult extends Model
{
    /** @use HasFactory<AutomationResultFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['warnings' => 'array', 'provider_metadata' => 'array'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AutomationRun::class, 'automation_run_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AutomationSource::class);
    }
}
