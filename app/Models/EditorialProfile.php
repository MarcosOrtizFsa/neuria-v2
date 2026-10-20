<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['media_outlet_id', 'name', 'language', 'identity', 'production', 'sources_config', 'restrictions', 'additional_instructions', 'current_version'])]
class EditorialProfile extends Model
{
    protected function casts(): array
    {
        return ['identity' => 'array', 'production' => 'array', 'sources_config' => 'array', 'restrictions' => 'array'];
    }

    public function mediaOutlet(): BelongsTo
    {
        return $this->belongsTo(MediaOutlet::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EditorialProfileVersion::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(EditorialSource::class);
    }
}
