<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['editorial_profile_id', 'name', 'url', 'type', 'priority', 'notes'])]
class EditorialSource extends Model
{
    public function editorialProfile(): BelongsTo
    {
        return $this->belongsTo(EditorialProfile::class);
    }
}
