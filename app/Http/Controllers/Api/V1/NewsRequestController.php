<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreNewsRequestRequest;
use App\Http\Resources\Api\V1\NewsRequestResource;
use App\Models\MediaApiCredential;
use App\Models\NewsRequest;
use App\Services\AuditService;
use App\Services\NewsWorkflowService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NewsRequestController extends Controller
{
    public function store(StoreNewsRequestRequest $request, NewsWorkflowService $workflow, AuditService $audit): JsonResponse
    {
        /** @var MediaApiCredential $credential */
        $credential = $request->attributes->get('media_api_credential');
        $data = $request->validated();
        $idempotencyKey = $request->idempotencyKey();
        $payloadHash = hash('sha256', json_encode(Arr::except($data, ['initial_urls_text']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        if ($idempotencyKey !== null) {
            $existing = $this->idempotentRequest($credential, $idempotencyKey);
            if ($existing) {
                return $this->idempotentResponse($request, $existing, $payloadHash);
            }
        }

        try {
            $newsRequest = $workflow->createAndDispatch($data, $credential->creator, $credential, $idempotencyKey, $payloadHash);
        } catch (UniqueConstraintViolationException $exception) {
            if ($idempotencyKey === null) {
                throw $exception;
            }

            $existing = $this->idempotentRequest($credential, $idempotencyKey);
            if (! $existing) {
                throw $exception;
            }

            return $this->idempotentResponse($request, $existing, $payloadHash);
        }

        $audit->record($request, 'CREAR_SOLICITUD_API', 'AGENCIA_NOTICIAS', $newsRequest, ['credential_id' => $credential->public_id]);

        return $this->acceptedResponse($request, $newsRequest);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        /** @var MediaApiCredential $credential */
        $credential = $request->attributes->get('media_api_credential');
        $newsRequest = NewsRequest::query()
            ->where('public_id', $publicId)
            ->whereHas('destinations', fn ($query) => $query->where('media_outlet_id', $credential->media_outlet_id))
            ->with(['destinations' => fn ($query) => $query
                ->where('media_outlet_id', $credential->media_outlet_id)
                ->with(['mediaChannel', 'draft'])])
            ->firstOrFail();

        return response()->json(['data' => (new NewsRequestResource($newsRequest))->resolve($request)])
            ->header('X-Records-Returned', '1');
    }

    private function idempotentRequest(MediaApiCredential $credential, string $idempotencyKey): ?NewsRequest
    {
        return NewsRequest::query()
            ->where('api_media_outlet_id', $credential->media_outlet_id)
            ->where('api_idempotency_key', $idempotencyKey)
            ->first();
    }

    private function idempotentResponse(Request $request, NewsRequest $newsRequest, string $payloadHash): JsonResponse
    {
        if (! hash_equals((string) $newsRequest->api_payload_hash, $payloadHash)) {
            return response()->json(['error' => [
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => 'La clave de idempotencia ya fue utilizada con otra solicitud.',
                'request_id' => $request->attributes->get('api_request_id'),
            ]], 409);
        }

        return $this->acceptedResponse($request, $newsRequest)->header('X-Idempotent-Replay', 'true');
    }

    private function acceptedResponse(Request $request, NewsRequest $newsRequest): JsonResponse
    {
        $newsRequest->load(['destinations.mediaChannel', 'destinations.draft']);

        return response()->json(['data' => (new NewsRequestResource($newsRequest))->resolve($request)], 202)
            ->header('Location', route('api.v1.requests.show', $newsRequest->public_id))
            ->header('X-Records-Returned', '1');
    }
}
