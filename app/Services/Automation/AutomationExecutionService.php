<?php

namespace App\Services\Automation;

use App\Models\AutomationRun;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutomationExecutionService
{
    public function __construct(
        private readonly AutomationHandlerRegistry $handlers,
        private readonly AutomationBudgetService $budgets,
        private readonly AutomationContentPublisher $publisher,
    ) {}

    public function execute(int $runId): void
    {
        $run = DB::transaction(function () use ($runId): ?AutomationRun {
            $run = AutomationRun::query()->lockForUpdate()->with(['task', 'version', 'quote'])->find($runId);
            if (! $run || ! in_array($run->status, ['PENDIENTE', 'DESPACHADA'], true)) {
                return null;
            }
            if ($run->version->version !== $run->task->current_version || ($run->trigger === 'PROGRAMADA' && $run->task->status !== 'ACTIVA') || $run->task->status === 'ARCHIVADA') {
                $run->update(['status' => 'CANCELADA', 'finished_at' => now(), 'sanitized_error' => 'La tarea ya no admite esta ejecución.']);

                return null;
            }
            $run->increment('attempts');
            $run->update(['status' => 'PROCESANDO', 'started_at' => now(), 'sanitized_error' => null]);

            return $run;
        });
        if (! $run) {
            return;
        }
        if (! $this->budgets->reserve($run)) {
            $run->update(['status' => 'BLOQUEADA', 'finished_at' => now(), 'sanitized_error' => 'Presupuesto, cuota diaria o concurrencia insuficiente.']);

            return;
        }

        try {
            $result = $this->handlers->for($run->task->type)->execute($run);
            DB::transaction(function () use ($run, $result): void {
                $characterCount = Str::length((string) ($result['body'] ?? ''));
                $configuration = $run->version->configuration;
                $isNoNews = $result['status'] === 'NO_NEWS';
                $allowedSources = collect($result['sources'])->filter(fn (array $source): bool => $this->sourceIsAllowed($source['url'] ?? '', $configuration['domains']))->values();
                $isValid = $isNoNews || ($characterCount >= $configuration['min_characters'] && $characterCount <= $configuration['max_characters'] && filled($result['title']) && filled($result['body']) && $allowedSources->isNotEmpty());
                $resultStatus = $isNoNews ? 'SIN_NOVEDADES' : ($isValid ? 'VALIDO' : 'INVALIDO');
                $saved = $run->result()->create(['status' => $resultStatus, 'title' => $result['title'], 'body' => $result['body'], 'character_count' => $characterCount, 'warnings' => $result['warnings'], 'provider_metadata' => $result['provider']]);
                foreach ($allowedSources as $source) {
                    $saved->sources()->firstOrCreate(['canonical_url_hash' => hash('sha256', $this->canonicalUrl($source['url']))], [
                        'title' => $source['title'], 'url' => $source['url'], 'publisher' => $source['publisher'] ?: null,
                        'published_at' => $source['published_at'] ?: null, 'consulted_at' => now(), 'citation' => $source['citation'] ?: null,
                        'publication_date_verified' => $source['publication_date_verified'],
                    ]);
                }
                foreach ($result['usage'] as $usage) {
                    $run->usageAttempts()->updateOrCreate(['attempt' => $usage['attempt'], 'operation' => $usage['operation']], $usage);
                }
                if ($isValid && ! $isNoNews) {
                    $this->publisher->publish($saved);
                }
                $run->update(['status' => $isNoNews ? 'SIN_NOVEDADES' : ($isValid ? 'COMPLETADA' : 'INVALIDA'), 'provider_response_id' => $result['provider']['response_id'], 'finished_at' => now(), 'sanitized_error' => $isValid ? null : 'La salida no cumplió longitud, fuentes permitidas o campos obligatorios.']);
            });
            if ($run->fresh()->status === 'COMPLETADA') {
                $this->budgets->settle($run);
            } else {
                $this->budgets->release($run, $run->fresh()->status === 'SIN_NOVEDADES' ? 'Sin novedades: sin cargo comercial' : 'Resultado inválido');
            }
        } catch (ConnectionException $exception) {
            $run->update(['status' => 'ESTADO_DESCONOCIDO', 'finished_at' => now(), 'sanitized_error' => 'El proveedor no confirmó si recibió la solicitud; requiere conciliación.']);
        } catch (\Throwable $exception) {
            report($exception);
            $run->update(['status' => 'ERROR', 'finished_at' => now(), 'sanitized_error' => 'La ejecución no pudo completarse. Consulte el diagnóstico administrativo.']);
            $this->budgets->release($run, 'Ejecución fallida');
        }
    }

    private function canonicalUrl(string $url): string
    {
        $parts = parse_url($url);

        return mb_strtolower(($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '/'));
    }

    private function sourceIsAllowed(string $url, array $allowedDomains): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return false;
        }
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        return collect($allowedDomains)->contains(fn (string $domain): bool => $host === $domain || str_ends_with($host, '.'.$domain));
    }
}
