<?php

namespace App\Http\Middleware;

use App\Models\MediaApiCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMediaApiScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        /** @var MediaApiCredential|null $credential */
        $credential = $request->attributes->get('media_api_credential');
        if (! $credential || ! in_array($scope, $credential->scopes ?? [], true)) {
            return response()->json(['error' => ['code' => 'INSUFFICIENT_SCOPE', 'message' => 'La credencial no tiene el permiso requerido.', 'request_id' => $request->attributes->get('api_request_id')]], 403);
        }

        return $next($request);
    }
}
