<?php

namespace App\Services;

use App\Models\GeneratedDraft;
use App\Models\MediaApiCredential;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class ContentDistributionQuery
{
    public function availableFor(MediaApiCredential $credential, ?int $channelId = null, ?string $after = null): Builder
    {
        if (! $credential->isActive() || ! in_array('content:read', $credential->scopes, true)) {
            throw new AuthorizationException('La credencial no puede consultar contenidos.');
        }
        $anchor = $after ? GeneratedDraft::query()->where('public_id', $after)->whereHas('destination', fn (Builder $query): Builder => $query->where('media_outlet_id', $credential->media_outlet_id))->first() : null;

        return GeneratedDraft::query()
            ->where('status', 'DISPONIBLE')
            ->whereHas('destination', function (Builder $query) use ($credential, $channelId): void {
                $query->where('media_outlet_id', $credential->media_outlet_id)
                    ->when($channelId, fn (Builder $channelQuery): Builder => $channelQuery->where('media_channel_id', $channelId));
            })
            ->when($anchor, fn (Builder $query): Builder => $query->where(function (Builder $cursor) use ($anchor): void {
                $cursor->where('available_at', '>', $anchor->available_at)
                    ->orWhere(fn (Builder $tie): Builder => $tie->where('available_at', $anchor->available_at)->where('id', '>', $anchor->id));
            }))
            ->orderBy('available_at')
            ->orderBy('id');
    }
}
