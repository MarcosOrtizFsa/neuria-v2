<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['news_request_id', 'processable_type', 'processable_id', 'idempotency_key', 'operation', 'status', 'attempts', 'error_message', 'started_at', 'completed_at'])]
class AiJobRun extends Model
{
    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function newsRequest(): BelongsTo
    {
        return $this->belongsTo(NewsRequest::class);
    }

    public function processable(): MorphTo
    {
        return $this->morphTo();
    }
}
