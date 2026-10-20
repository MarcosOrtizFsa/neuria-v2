<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['news_request_id', 'ai_job_run_id', 'openai_response_id', 'model', 'operation', 'input_tokens', 'output_tokens', 'total_tokens', 'estimated_cost', 'currency'])]
class AiUsage extends Model
{
    protected function casts(): array
    {
        return ['estimated_cost' => 'decimal:6'];
    }

    public function newsRequest(): BelongsTo
    {
        return $this->belongsTo(NewsRequest::class);
    }

    public function jobRun(): BelongsTo
    {
        return $this->belongsTo(AiJobRun::class, 'ai_job_run_id');
    }
}
