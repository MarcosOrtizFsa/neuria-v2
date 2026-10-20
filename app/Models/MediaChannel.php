<?php

namespace App\Models;

use Database\Factories\MediaChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['media_outlet_id', 'name', 'type', 'url', 'external_identifier', 'status', 'editorial_overrides'])]
class MediaChannel extends Model
{
    /** @use HasFactory<MediaChannelFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['editorial_overrides' => 'array'];
    }

    public function mediaOutlet(): BelongsTo
    {
        return $this->belongsTo(MediaOutlet::class);
    }
}
