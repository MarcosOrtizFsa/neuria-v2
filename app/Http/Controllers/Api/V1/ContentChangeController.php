<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ContentChangeIndexRequest;
use App\Http\Resources\Api\V1\ContentChangeResource;
use App\Models\ContentDistributionEvent;
use App\Models\MediaApiCredential;
use App\Services\ApiCursor;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ContentChangeController extends Controller
{
    public function __invoke(ContentChangeIndexRequest $request, ApiCursor $cursors): JsonResponse
    {
        /** @var MediaApiCredential $credential */
        $credential = $request->attributes->get('media_api_credential');
        $data = $request->validated();
        $limit = (int) ($data['limit'] ?? config('services.media_api.default_page_size', 20));
        $cursor = $cursors->decode($data['cursor'] ?? null, 'changes', $credential->media_outlet_id);
        $filter = hash('sha256', json_encode([$data['channel_id'] ?? null]));
        if ($cursor && ($cursor['filter'] ?? null) !== $filter) {
            throw ValidationException::withMessages(['cursor' => 'El cursor no corresponde a los filtros enviados.']);
        }
        $events = ContentDistributionEvent::query()
            ->where('media_outlet_id', $credential->media_outlet_id)
            ->when($data['channel_id'] ?? null, fn ($query, $channelId) => $query->where('media_channel_id', $channelId))
            ->when($cursor, fn ($query) => $query->where('id', '>', $cursor['id']))
            ->with(['draft:id,public_id,title,status', 'mediaChannel:id,name,type'])
            ->orderBy('id')->limit($limit + 1)->get();
        $hasMore = $events->count() > $limit;
        $events = $events->take($limit)->values();
        $last = $events->last();
        $nextCursor = $last ? $cursors->encode(['type' => 'changes', 'media' => $credential->media_outlet_id, 'filter' => $filter, 'id' => $last->id]) : null;

        return response()->json([
            'data' => ContentChangeResource::collection($events)->resolve($request),
            'meta' => ['next_cursor' => $nextCursor, 'limit' => $limit, 'has_more' => $hasMore],
        ])->header('X-Records-Returned', (string) $events->count());
    }
}
