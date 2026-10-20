<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['media_outlet_id', 'created_by', 'name', 'public_id', 'token_hash', 'token_prefix', 'scopes', 'expires_at', 'revoked_at', 'last_used_at'])]
#[Hidden(['token_hash'])]
class MediaApiCredential extends Model
{
    protected function casts(): array
    {
        return ['scopes' => 'array', 'expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_used_at' => 'datetime'];
    }

    public function mediaOutlet(): BelongsTo
    {
        return $this->belongsTo(MediaOutlet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
