<?php

namespace App\Models;

use Database\Factories\NewsRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'requested_by', 'api_media_outlet_id', 'api_credential_id', 'api_idempotency_key', 'api_payload_hash', 'title', 'description', 'context', 'specific_instructions', 'category', 'priority', 'event_at', 'web_search_enabled', 'initial_urls', 'research_notes', 'status', 'progress'])]
class NewsRequest extends Model
{
    public const TERMINAL_STATUSES = ['COMPLETADA', 'PARCIALMENTE_COMPLETADA', 'ERROR', 'CANCELADA'];

    /** @use HasFactory<NewsRequestFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (NewsRequest $newsRequest): void {
            $newsRequest->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return ['event_at' => 'datetime', 'web_search_enabled' => 'boolean', 'initial_urls' => 'array'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(NewsRequestDestination::class);
    }

    public function researches(): HasMany
    {
        return $this->hasMany(NewsResearch::class);
    }

    public function jobRuns(): HasMany
    {
        return $this->hasMany(AiJobRun::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(AiUsage::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(NewsRequestStatusHistory::class);
    }

    public function drafts(): HasManyThrough
    {
        return $this->hasManyThrough(GeneratedDraft::class, NewsRequestDestination::class);
    }
}
