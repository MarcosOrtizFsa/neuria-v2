<?php

namespace App\Models;

use Database\Factories\MediaOutletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['organization_id', 'name', 'trade_name', 'public_identifier', 'main_url', 'description', 'contact_email', 'contact_phone', 'type', 'status', 'internal_notes'])]
class MediaOutlet extends Model
{
    /** @use HasFactory<MediaOutletFactory> */
    use HasFactory, SoftDeletes;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(MediaChannel::class);
    }

    public function apiCredentials(): HasMany
    {
        return $this->hasMany(MediaApiCredential::class);
    }

    public function editorialProfile(): HasOne
    {
        return $this->hasOne(EditorialProfile::class);
    }

    public function apiUsageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLog::class);
    }
}
