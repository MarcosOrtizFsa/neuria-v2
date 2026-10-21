<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'title' => $this->title,
            'subtitle' => $this->summary,
            'summary' => $this->summary,
            'body' => $this->body,
            'category' => $this->category,
            'tags' => $this->tags ?? [],
            'channel' => [
                'name' => $this->destination->mediaChannel->name,
                'type' => $this->destination->mediaChannel->type,
            ],
            'generated_at' => $this->generated_at?->toIso8601String(),
            'available_at' => $this->available_at?->toIso8601String(),
            'status' => $this->status,
            'sources' => $this->research->sources->map(fn ($source): array => [
                'title' => $source->title,
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
