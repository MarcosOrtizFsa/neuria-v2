<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['editorial_profile_id', 'user_id', 'version', 'configuration', 'change_note'])]
class EditorialProfileVersion extends Model
{
    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function editorialProfile(): BelongsTo
    {
        return $this->belongsTo(EditorialProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
