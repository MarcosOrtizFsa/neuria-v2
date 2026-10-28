<?php

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use App\Models\MediaApiCredential;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LogApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $this->write($request, match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                $exception instanceof AuthorizationException => 403,
                default => 500,
            }, 0, $startedAt);
            throw $exception;
        }
        $this->write($request, $response->getStatusCode(), (int) $response->headers->get('X-Records-Returned', 0), $startedAt);

        return $response;
    }

    private function write(Request $request, int $status, int $records, int $startedAt): void
    {
        /** @var MediaApiCredential|null $credential */
        $credential = $request->attributes->get('media_api_credential');

        ApiUsageLog::create([
            'request_id' => $request->attributes->get('api_request_id', (string) Str::uuid()),
            'media_outlet_id' => $credential?->media_outlet_id,
            'media_api_credential_id' => $credential?->id,
            'endpoint' => $request->route()?->getName() ?? $request->path(),
            'method' => $request->method(),
            'status_code' => $status,
            'records_returned' => $records,
            'duration_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
            'result' => match (true) {
                $status === 401 || $status === 403 => 'AUTH_ERROR',
                $status === 429 => 'RATE_LIMITED',
                $status >= 400 => 'ERROR',
                default => 'SUCCESS',
            },
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
        ]);
    }
}
