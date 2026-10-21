<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ContentIndexRequest;
use App\Http\Resources\Api\V1\ContentResource;
use App\Models\MediaApiCredential;
use App\Services\ApiCursor;
use App\Services\ContentDistributionQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public function index(ContentIndexRequest $request, ContentDistributionQuery $contents, ApiCursor $cursors): JsonResponse
    {
        /** @var MediaApiCredential $credential */
        $credential = $request->attributes->get('media_api_credential');
        $data = $request->validated();
        $limit = (int) ($data['limit'] ?? config('services.media_api.default_page_size', 20));
        $cursor = $cursors->decode($data['cursor'] ?? null, 'contents', $credential->media_outlet_id);
        $filter = hash('sha256', json_encode([$data['channel_id'] ?? null, $data['available_from'] ?? null, $data['id'] ?? null]));
        if ($cursor && ($cursor['filter'] ?? null) !== $filter) {
            throw ValidationException::withMessages(['cursor' => 'El cursor no corresponde a los filtros enviados.']);
        }
        $items = $contents->availableFor($credential, $data['channel_id'] ?? null, $cursor, $data['available_from'] ?? null, $data['id'] ?? null)
            ->with(['destination.mediaChannel:id,name,type', 'research.sources', 'editorialProfileVersion:id,version'])
            ->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        $items = $items->take($limit)->values();
        $last = $items->last();
        $nextCursor = $last ? $cursors->encode([
            'type' => 'contents', 'media' => $credential->media_outlet_id,
            'filter' => $filter, 'available_at' => $last->available_at->format('Y-m-d H:i:s.u'), 'id' => $last->id,
        ]) : null;

        return response()->json([
            'data' => ContentResource::collection($items)->resolve($request),
            'meta' => ['next_cursor' => $nextCursor, 'limit' => $limit, 'has_more' => $hasMore],
        ])->header('X-Records-Returned', (string) $items->count());
    }

    public function show(ContentIndexRequest $request, string $publicId, ContentDistributionQuery $contents): JsonResponse
    {
        /** @var MediaApiCredential $credential */
        $credential = $request->attributes->get('media_api_credential');
        $content = $contents->availableFor($credential, publicId: $publicId)
            ->with(['destination.mediaChannel:id,name,type', 'research.sources', 'editorialProfileVersion:id,version'])->firstOrFail();

        return response()->json(['data' => (new ContentResource($content))->resolve($request)])
            ->header('X-Records-Returned', '1');
    }
}
