<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['news_request_destination_id', 'news_research_id', 'editorial_profile_version_id', 'title', 'summary', 'body', 'category', 'tags', 'source_references', 'editorial_warnings', 'status', 'model'])]
class GeneratedDraft extends Model
{
    protected function casts(): array
    {
        return ['tags' => 'array', 'source_references' => 'array', 'editorial_warnings' => 'array'];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(NewsRequestDestination::class, 'news_request_destination_id');
    }

    public function research(): BelongsTo
    {
        return $this->belongsTo(NewsResearch::class, 'news_research_id');
    }
}
