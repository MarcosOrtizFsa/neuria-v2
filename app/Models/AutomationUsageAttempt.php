<?php

namespace App\Models;

use Database\Factories\AutomationUsageAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['automation_run_id', 'attempt', 'operation', 'model', 'provider_response_id', 'input_tokens', 'output_tokens', 'total_tokens', 'tool_calls', 'technical_cost_minor', 'currency', 'cost_condition', 'status'])]
class AutomationUsageAttempt extends Model
{
    /** @use HasFactory<AutomationUsageAttemptFactory> */
    use HasFactory;

    public function run(): BelongsTo
    {
        return $this->belongsTo(AutomationRun::class, 'automation_run_id');
    }
}
