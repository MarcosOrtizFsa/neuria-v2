<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'generated_draft_id', 'content_status_history_id', 'media_outlet_id', 'media_channel_id', 'event_type', 'content_public_id', 'occurred_at'])]
class ContentDistributionEvent extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (self $event) => $event->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(GeneratedDraft::class, 'generated_draft_id');
    }

    public function mediaOutlet(): BelongsTo
    {
        return $this->belongsTo(MediaOutlet::class);
    }

    public function mediaChannel(): BelongsTo
    {
        return $this->belongsTo(MediaChannel::class);
    }
}
