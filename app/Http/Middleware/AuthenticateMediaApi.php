<?php

namespace App\Http\Middleware;

use App\Services\MediaCredentialService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMediaApi
{
    public function __construct(private readonly MediaCredentialService $credentials) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $credential = is_string($token) && $token !== '' ? $this->credentials->verify($token) : null;
        $credential?->loadMissing('mediaOutlet.organization');

        if (! $credential || $credential->mediaOutlet?->status !== 'ACTIVO' || ! $credential->mediaOutlet->organization?->is_active) {
            return $this->error();
        }

        $request->attributes->set('media_api_credential', $credential);
        $request->attributes->set('media_outlet', $credential->mediaOutlet);
        if ($credential->last_used_at === null || $credential->last_used_at->lt(now()->subMinutes(5))) {
            $credential->update(['last_used_at' => now()]);
        }

        return $next($request);
    }

    private function error(): JsonResponse
    {
        return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Credencial inválida o inactiva.', 'request_id' => request()->attributes->get('api_request_id')]], 401);
    }
}
