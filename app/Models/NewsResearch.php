<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['news_request_id', 'version', 'status', 'model', 'openai_response_id', 'result', 'warnings', 'error_message', 'started_at', 'completed_at'])]
class NewsResearch extends Model
{
    protected $table = 'news_research';

    protected function casts(): array
    {
        return ['result' => 'array', 'warnings' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function newsRequest(): BelongsTo
    {
        return $this->belongsTo(NewsRequest::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ResearchSource::class);
    }
}
