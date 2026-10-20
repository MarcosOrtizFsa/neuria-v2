<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['news_request_id', 'media_outlet_id', 'media_channel_id', 'editorial_profile_version_id', 'status', 'error_message'])]
class NewsRequestDestination extends Model
{
    public function newsRequest(): BelongsTo
    {
        return $this->belongsTo(NewsRequest::class);
    }

    public function mediaOutlet(): BelongsTo
    {
        return $this->belongsTo(MediaOutlet::class);
    }

    public function mediaChannel(): BelongsTo
    {
        return $this->belongsTo(MediaChannel::class);
    }

    public function editorialProfileVersion(): BelongsTo
    {
        return $this->belongsTo(EditorialProfileVersion::class);
    }

    public function draft(): HasOne
    {
        return $this->hasOne(GeneratedDraft::class);
    }

    public function jobRuns(): MorphMany
    {
        return $this->morphMany(AiJobRun::class, 'processable');
    }
}
