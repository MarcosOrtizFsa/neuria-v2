<?php

namespace App\Models;

use Database\Factories\AutomationSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['automation_result_id', 'title', 'url', 'canonical_url_hash', 'publisher', 'published_at', 'consulted_at', 'citation', 'publication_date_verified'])]
class AutomationSource extends Model
{
    /** @use HasFactory<AutomationSourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'consulted_at' => 'datetime', 'publication_date_verified' => 'boolean'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AutomationResult::class, 'automation_result_id');
    }
}
