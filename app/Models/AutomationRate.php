<?php

namespace App\Models;

use Database\Factories\AutomationRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'version', 'name', 'task_type', 'price_per_run_minor', 'currency', 'policies', 'valid_from', 'valid_until', 'is_demo', 'is_active'])]
class AutomationRate extends Model
{
    /** @use HasFactory<AutomationRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['policies' => 'array', 'valid_from' => 'datetime', 'valid_until' => 'datetime', 'is_demo' => 'boolean', 'is_active' => 'boolean'];
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(AutomationQuote::class);
    }
}
