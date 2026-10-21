<?php

namespace App\Services;

use App\Models\ContentStatusHistory;
use App\Models\GeneratedDraft;

class ContentDistributionEventService
{
    public function record(GeneratedDraft $draft, ContentStatusHistory $history): void
    {
        $eventType = match (true) {
            $history->to_status === 'DISPONIBLE' && $history->from_status === 'SUSPENDIDO' => 'CONTENT_REACTIVATED',
            $history->to_status === 'DISPONIBLE' => 'CONTENT_AVAILABLE',
            $history->to_status === 'SUSPENDIDO' => 'CONTENT_SUSPENDED',
            $history->to_status === 'RETIRADO' => 'CONTENT_WITHDRAWN',
            default => null,
        };
        if (! $eventType) {
            return;
        }

        $draft->loadMissing('destination');
        $draft->distributionEvents()->firstOrCreate(
            ['content_status_history_id' => $history->id],
            [
                'media_outlet_id' => $draft->destination->media_outlet_id,
                'media_channel_id' => $draft->destination->media_channel_id,
                'event_type' => $eventType,
                'content_public_id' => $draft->public_id,
                'occurred_at' => $history->created_at,
            ],
        );
    }
}
