<?php

namespace App\Jobs;

use App\Models\AiJobRun;
use App\Models\AiUsage;
use App\Models\AuditLog;
use App\Models\GeneratedDraft;
use App\Models\NewsRequest;
use App\Models\NewsRequestDestination;
use App\Models\NewsResearch;
use App\Services\ContentAvailabilityService;
use App\Services\NewsWorkflowService;
use App\Services\OpenAIContentGenerationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateDestinationContent implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 600;

    public function __construct(public int $newsRequestId, public int $researchId, public int $destinationId, public int $jobRunId) {}

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function uniqueId(): string
    {
        return "destination:{$this->destinationId}";
    }

    public function handle(OpenAIContentGenerationService $openAI, NewsWorkflowService $workflow, ContentAvailabilityService $availability): void
    {
        $destination = NewsRequestDestination::with(['newsRequest', 'mediaOutlet.editorialProfile', 'mediaChannel', 'editorialProfileVersion'])->findOrFail($this->destinationId);
        $research = NewsResearch::findOrFail($this->researchId);
        $jobRun = AiJobRun::findOrFail($this->jobRunId);
        if ($jobRun->status === 'COMPLETADA' || GeneratedDraft::where('news_request_destination_id', $destination->id)->exists()) {
            return;
        }

        $destination->update(['status' => 'GENERANDO', 'error_message' => null]);
        $jobRun->update(['status' => 'PROCESANDO', 'attempts' => $this->attempts(), 'started_at' => now(), 'error_message' => null]);
        $result = $openAI->generate($research, $destination);

        DB::transaction(function () use ($destination, $research, $jobRun, $result, $workflow, $availability): void {
            $data = $result['data'];
            $draft = GeneratedDraft::firstOrCreate(['news_request_destination_id' => $destination->id], [
                'news_research_id' => $research->id,
                'editorial_profile_version_id' => $destination->editorial_profile_version_id,
                'title' => $data['title'],
                'summary' => $data['summary'],
                'body' => strip_tags($data['body']),
                'category' => $data['category'],
                'tags' => $data['tags'],
                'source_references' => $data['source_references'],
                'editorial_warnings' => $data['editorial_warnings'],
                'status' => 'GENERANDO',
                'model' => $result['model'],
                'generated_at' => now(),
            ]);
            $isAvailable = $availability->validateAndMakeAvailable($draft);
            $destination->update(['status' => $isAvailable ? 'COMPLETADA' : 'ERROR', 'error_message' => $isAvailable ? null : $draft->fresh()->technical_error]);
            $jobRun->update(['status' => $isAvailable ? 'COMPLETADA' : 'ERROR', 'error_message' => $isAvailable ? null : $draft->fresh()->technical_error, 'completed_at' => now()]);
            AuditLog::create(['user_id' => $destination->newsRequest->requested_by, 'action' => $isAvailable ? 'CONTENIDO_DISPONIBLE' : 'VALIDACION_CONTENIDO_ERROR', 'module' => 'CONTENIDOS', 'auditable_type' => $draft->getMorphClass(), 'auditable_id' => $draft->id, 'result' => $isAvailable ? 'SUCCESS' : 'ERROR', 'metadata' => ['status' => $draft->fresh()->status]]);
            if ($result['id']) {
                AiUsage::updateOrCreate(['openai_response_id' => $result['id']], [
                    'news_request_id' => $destination->news_request_id,
                    'ai_job_run_id' => $jobRun->id,
                    'model' => $result['model'],
                    'operation' => 'GENERACION',
                    'input_tokens' => $result['usage']['input_tokens'] ?? null,
                    'output_tokens' => $result['usage']['output_tokens'] ?? null,
                    'total_tokens' => $result['usage']['total_tokens'] ?? null,
                ]);
            }
            $this->finishRequestIfReady($destination->newsRequest, $workflow);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $message = Str::limit($exception?->getMessage() ?? 'Error desconocido', 2000);
        NewsRequestDestination::whereKey($this->destinationId)->update(['status' => 'ERROR', 'error_message' => $message]);
        AiJobRun::whereKey($this->jobRunId)->update(['status' => 'ERROR', 'error_message' => $message, 'completed_at' => now()]);
        $request = NewsRequest::find($this->newsRequestId);
        if ($request) {
            $this->finishRequestIfReady($request, app(NewsWorkflowService::class));
        }
    }

    private function finishRequestIfReady(NewsRequest $request, NewsWorkflowService $workflow): void
    {
        $request = NewsRequest::query()->lockForUpdate()->findOrFail($request->id);
        $statuses = $request->destinations()->pluck('status');
        if ($statuses->contains(fn (string $status): bool => in_array($status, ['PENDIENTE', 'GENERANDO'], true))) {
            $completed = $statuses->filter(fn (string $status): bool => $status === 'COMPLETADA')->count();
            $request->update(['progress' => 55 + (int) floor(45 * ($completed / max(1, $statuses->count())))]);

            return;
        }

        $completed = $statuses->filter(fn (string $status): bool => $status === 'COMPLETADA')->count();
        $target = $completed === $statuses->count() ? 'COMPLETADA' : ($completed > 0 ? 'PARCIALMENTE_COMPLETADA' : 'ERROR');
        $workflow->transition($request, $target, reason: 'Finalizó el procesamiento de todos los destinos.');
        $request->update(['progress' => $target === 'COMPLETADA' ? 100 : 95]);
    }
}
