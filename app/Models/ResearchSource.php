<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['news_research_id', 'title', 'url', 'url_hash', 'publisher', 'published_at', 'consulted_at', 'relevant_summary', 'verified'])]
class ResearchSource extends Model
{
    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'consulted_at' => 'datetime', 'verified' => 'boolean'];
    }

    public function research(): BelongsTo
    {
        return $this->belongsTo(NewsResearch::class, 'news_research_id');
    }
}
