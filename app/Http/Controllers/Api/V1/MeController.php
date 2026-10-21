<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MediaApiCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var MediaApiCredential $credential */
        $credential = $request->attributes->get('media_api_credential');
        $media = $credential->mediaOutlet;

        return response()->json(['data' => [
            'credential' => ['id' => $credential->public_id, 'name' => $credential->name, 'prefix' => $credential->token_prefix, 'scopes' => $credential->scopes, 'expires_at' => $credential->expires_at?->toIso8601String()],
            'media' => ['id' => $media->public_identifier, 'name' => $media->name, 'status' => $media->status],
            'organization' => ['name' => $media->organization->name],
        ]])->header('X-Records-Returned', '1');
    }
}
