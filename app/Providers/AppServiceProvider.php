<?php

namespace App\Providers;

use App\Contracts\AutomationHandler;
use App\Contracts\AutomationProvider;
use App\Models\ApiUsageLog;
use App\Models\MediaApiCredential;
use App\Services\Automation\AutomationHandlerRegistry;
use App\Services\Automation\NewsResearchAutomationHandler;
use App\Services\Automation\OpenAIAutomationProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AutomationProvider::class, OpenAIAutomationProvider::class);
        $this->app->bind(AutomationHandler::class, NewsResearchAutomationHandler::class);
        $this->app->singleton(AutomationHandlerRegistry::class, fn ($app) => new AutomationHandlerRegistry([$app->make(AutomationHandler::class)]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('automation-interpret', fn (Request $request): Limit => Limit::perMinute(
            config('services.automation.interpret_rate_limit', 5)
        )->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('automation-run-now', fn (Request $request): Limit => Limit::perMinute(
            config('services.automation.run_now_rate_limit', 5)
        )->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('media-api', function (Request $request): Limit {
            /** @var MediaApiCredential|null $credential */
            $credential = $request->attributes->get('media_api_credential');
            $endpoint = $request->route()?->getName() ?? $request->path();

            return Limit::perMinute(config('services.media_api.rate_limit_per_minute', 60))
                ->by(($credential?->id ?? hash('sha256', (string) $request->ip())).'|'.$endpoint)
                ->response(function (Request $request, array $headers) use ($credential, $endpoint) {
                    ApiUsageLog::create([
                        'request_id' => $request->attributes->get('api_request_id', (string) Str::uuid()),
                        'media_outlet_id' => $credential?->media_outlet_id,
                        'media_api_credential_id' => $credential?->id,
                        'endpoint' => $endpoint,
                        'method' => $request->method(),
                        'status_code' => 429,
                        'records_returned' => 0,
                        'duration_ms' => 0,
                        'result' => 'RATE_LIMITED',
                        'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
                    ]);

                    return response()->json(['error' => ['code' => 'RATE_LIMIT_EXCEEDED', 'message' => 'Se alcanzó el límite temporal de solicitudes.', 'request_id' => $request->attributes->get('api_request_id')]], 429, $headers);
                });
        });
    }
}
