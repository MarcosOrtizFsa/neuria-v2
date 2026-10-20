<?php

namespace App\Jobs;

use App\Models\AiJobRun;
use App\Models\AiUsage;
use App\Models\AuditLog;
use App\Models\NewsRequest;
use App\Models\NewsResearch;
use App\Services\NewsWorkflowService;
use App\Services\OpenAIResearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RunCentralResearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $newsRequestId, public int $researchId, public int $jobRunId) {}

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(OpenAIResearchService $openAI, NewsWorkflowService $workflow): void
    {
        $request = NewsRequest::with('destinations')->findOrFail($this->newsRequestId);
        $research = NewsResearch::findOrFail($this->researchId);
        $jobRun = AiJobRun::findOrFail($this->jobRunId);
        if ($jobRun->status === 'COMPLETADA' || $research->status === 'COMPLETADA') {
            return;
        }

        DB::transaction(function () use ($request, $research, $jobRun, $workflow): void {
            $workflow->transition($request, 'INVESTIGANDO', reason: 'Comenzó la investigación central.');
            $request->update(['progress' => 15]);
            $research->update(['status' => 'PROCESANDO', 'started_at' => now(), 'error_message' => null]);
            $jobRun->update(['status' => 'PROCESANDO', 'attempts' => $this->attempts(), 'started_at' => now(), 'error_message' => null]);
        });

        $result = $openAI->research($request);

        DB::transaction(function () use ($request, $research, $jobRun, $workflow, $result): void {
            $data = $result['data'];
            $research->update([
                'status' => 'COMPLETADA',
                'model' => $result['model'],
                'openai_response_id' => $result['id'],
                'result' => $data,
                'warnings' => $data['warnings'] ?? [],
                'completed_at' => now(),
            ]);

            foreach ($data['sources'] ?? [] as $source) {
                if (! filter_var($source['url'] ?? null, FILTER_VALIDATE_URL)) {
                    continue;
                }
                $research->sources()->updateOrCreate(
                    ['url_hash' => hash('sha256', $source['url'])],
                    ['title' => $source['title'], 'url' => $source['url'], 'publisher' => $source['publisher'] ?: null, 'consulted_at' => now(), 'relevant_summary' => $source['relevant_summary'] ?: null, 'verified' => false],
                );
            }

            $this->recordUsage($request, $jobRun, $result, 'INVESTIGACION');
            $jobRun->update(['status' => 'COMPLETADA', 'completed_at' => now()]);
            AuditLog::create(['user_id' => $request->requested_by, 'action' => 'INVESTIGACION_COMPLETADA', 'module' => 'AGENCIA_NOTICIAS', 'auditable_type' => $request->getMorphClass(), 'auditable_id' => $request->id, 'result' => 'SUCCESS', 'metadata' => ['research_id' => $research->id, 'sources' => $research->sources()->count()]]);
            $workflow->transition($request, 'INVESTIGACION_COMPLETA', reason: 'OpenAI devolvió el expediente estructurado.');
            $workflow->transition($request, 'GENERANDO', reason: 'Comenzó la generación por destino.');
            $request->update(['progress' => 55]);

            foreach ($request->destinations as $destination) {
                $generationRun = AiJobRun::firstOrCreate(
                    ['news_request_id' => $request->id, 'processable_type' => $destination->getMorphClass(), 'processable_id' => $destination->id, 'operation' => 'GENERACION'],
                    ['idempotency_key' => (string) Str::uuid(), 'status' => 'PENDIENTE'],
                );
                DB::afterCommit(fn () => GenerateDestinationContent::dispatch($request->id, $research->id, $destination->id, $generationRun->id));
            }
        });
    }

    public function failed(?Throwable $exception): void
    {
        $request = NewsRequest::find($this->newsRequestId);
        NewsResearch::whereKey($this->researchId)->update(['status' => 'ERROR', 'error_message' => Str::limit($exception?->getMessage() ?? 'Error desconocido', 2000)]);
        AiJobRun::whereKey($this->jobRunId)->update(['status' => 'ERROR', 'error_message' => Str::limit($exception?->getMessage() ?? 'Error desconocido', 2000), 'completed_at' => now()]);
        if ($request && $request->status === 'INVESTIGANDO') {
            app(NewsWorkflowService::class)->transition($request, 'ERROR', reason: 'Falló la investigación luego de agotar los reintentos.');
            $request->update(['progress' => 0]);
            AuditLog::create(['user_id' => $request->requested_by, 'action' => 'INVESTIGACION_ERROR', 'module' => 'AGENCIA_NOTICIAS', 'auditable_type' => $request->getMorphClass(), 'auditable_id' => $request->id, 'result' => 'ERROR', 'metadata' => ['message' => Str::limit($exception?->getMessage() ?? 'Error desconocido', 500)]]);
        }
    }

    private function recordUsage(NewsRequest $request, AiJobRun $jobRun, array $result, string $operation): void
    {
        if (! $result['id']) {
            return;
        }
        AiUsage::updateOrCreate(['openai_response_id' => $result['id']], [
            'news_request_id' => $request->id,
            'ai_job_run_id' => $jobRun->id,
            'model' => $result['model'],
            'operation' => $operation,
            'input_tokens' => $result['usage']['input_tokens'] ?? null,
            'output_tokens' => $result['usage']['output_tokens'] ?? null,
            'total_tokens' => $result['usage']['total_tokens'] ?? null,
        ]);
    }
}
