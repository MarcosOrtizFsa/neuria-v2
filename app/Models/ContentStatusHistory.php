<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['generated_draft_id', 'user_id', 'from_status', 'to_status', 'reason'])]
class ContentStatusHistory extends Model
{
    public function draft(): BelongsTo
    {
        return $this->belongsTo(GeneratedDraft::class, 'generated_draft_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
