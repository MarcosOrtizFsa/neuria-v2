<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['public_id', 'news_request_destination_id', 'news_research_id', 'editorial_profile_version_id', 'title', 'summary', 'body', 'category', 'tags', 'source_references', 'editorial_warnings', 'status', 'model', 'generated_at', 'available_at', 'suspended_at', 'retired_at', 'technical_error'])]
class GeneratedDraft extends Model
{
    protected static function booted(): void
    {
        static::creating(function (GeneratedDraft $draft): void {
            $draft->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return ['tags' => 'array', 'source_references' => 'array', 'editorial_warnings' => 'array', 'generated_at' => 'datetime', 'available_at' => 'datetime', 'suspended_at' => 'datetime', 'retired_at' => 'datetime'];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(NewsRequestDestination::class, 'news_request_destination_id');
    }

    public function research(): BelongsTo
    {
        return $this->belongsTo(NewsResearch::class, 'news_research_id');
    }

    public function editorialProfileVersion(): BelongsTo
    {
        return $this->belongsTo(EditorialProfileVersion::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ContentStatusHistory::class);
    }
}
