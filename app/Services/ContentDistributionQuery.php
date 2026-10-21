<?php

namespace App\Services;

use App\Models\GeneratedDraft;
use App\Models\MediaApiCredential;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class ContentDistributionQuery
{
    public function availableFor(MediaApiCredential $credential, ?int $channelId = null, ?array $cursor = null, ?string $availableFrom = null, ?string $publicId = null): Builder
    {
        if (! $credential->isActive() || ! in_array('content:read', $credential->scopes, true)) {
            throw new AuthorizationException('La credencial no puede consultar contenidos.');
        }

        return GeneratedDraft::query()
            ->where('status', 'DISPONIBLE')
            ->whereHas('destination', function (Builder $query) use ($credential, $channelId): void {
                $query->where('media_outlet_id', $credential->media_outlet_id)
                    ->when($channelId, fn (Builder $channelQuery): Builder => $channelQuery->where('media_channel_id', $channelId));
            })
            ->when($availableFrom, fn (Builder $query): Builder => $query->where('available_at', '>=', $availableFrom))
            ->when($publicId, fn (Builder $query): Builder => $query->where('public_id', $publicId))
            ->when($cursor, fn (Builder $query): Builder => $query->where(function (Builder $position) use ($cursor): void {
                $position->where('available_at', '>', $cursor['available_at'])
                    ->orWhere(fn (Builder $tie): Builder => $tie->where('available_at', $cursor['available_at'])->where('id', '>', $cursor['id']));
            }))
            ->orderBy('available_at')
            ->orderBy('id');
    }
}
