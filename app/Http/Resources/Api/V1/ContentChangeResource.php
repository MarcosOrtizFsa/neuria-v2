<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentChangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'type' => $this->event_type,
            'content_uuid' => $this->content_public_id,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'content' => [
                'id' => $this->content_public_id,
                'status' => match ($this->event_type) {
                    'CONTENT_AVAILABLE', 'CONTENT_REACTIVATED' => 'DISPONIBLE',
                    'CONTENT_SUSPENDED' => 'SUSPENDIDO',
                    'CONTENT_WITHDRAWN' => 'RETIRADO',
                    default => $this->draft->status,
                },
                'title' => $this->draft->title,
                'channel' => [
                    'name' => $this->mediaChannel->name,
                    'type' => $this->mediaChannel->type,
                ],
            ],
        ];
    }
}
