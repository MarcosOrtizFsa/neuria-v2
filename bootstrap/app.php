<?php

use App\Http\Middleware\AssignApiRequestId;
use App\Http\Middleware\AuthenticateMediaApi;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureMediaApiScope;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogApiUsage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'permission' => CheckPermission::class,
            'media.api' => AuthenticateMediaApi::class,
            'media.scope' => EnsureMediaApiScope::class,
            'api.usage' => LogApiUsage::class,
            'api.request-id' => AssignApiRequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Los parámetros enviados no son válidos.', 'request_id' => $request->attributes->get('api_request_id'), 'details' => $exception->errors()]], 422);
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'El recurso solicitado no está disponible.', 'request_id' => $request->attributes->get('api_request_id')]], 404);
        });
    })->create();
