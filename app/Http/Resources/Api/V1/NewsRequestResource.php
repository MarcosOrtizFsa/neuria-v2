<?php

namespace App\Http\Resources\Api\V1;

use App\Models\NewsRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isTerminal = in_array($this->status, NewsRequest::TERMINAL_STATUSES, true);

        return [
            'id' => $this->public_id,
            'title' => $this->title,
            'status' => $this->status,
            'progress' => $this->progress,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'destinations' => $isTerminal ? $this->destinations->map(fn ($destination): array => [
                'channel' => [
                    'id' => $destination->mediaChannel->id,
                    'name' => $destination->mediaChannel->name,
                    'type' => $destination->mediaChannel->type,
                ],
                'status' => $destination->status,
                'content_id' => $destination->draft?->status === 'DISPONIBLE' ? $destination->draft->public_id : null,
                'content_status' => $destination->draft?->status === 'DISPONIBLE' ? 'AVAILABLE' : null,
                'error' => $destination->status === 'ERROR' ? 'No fue posible generar el contenido para este destino.' : null,
            ])->values() : [],
        ];
    }
}
