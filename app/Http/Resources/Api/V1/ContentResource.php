<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->public_id,
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body,
            'category' => $this->category,
            'tags' => $this->tags ?? [],
            'channel_id' => $this->destination->mediaChannel->id,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'available_from' => $this->available_at?->toIso8601String(),
            'status' => 'AVAILABLE',
            'sources' => $this->research->sources->map(fn ($source): array => [
                'name' => $source->title,
                'url' => $source->url,
                'publisher' => $source->publisher,
                'consulted_at' => $source->consulted_at?->toIso8601String(),
            ])->values(),
            'metadata' => [
                'generated_by_ai' => true,
                'editorial_reviewed_by_neuria' => false,
                'editorial_profile_version' => $this->editorialProfileVersion?->version,
            ],
        ];
    }
}
