<?php

namespace App\Services;

use App\Jobs\GenerateDestinationContent;
use App\Jobs\RunCentralResearch;
use App\Models\AiJobRun;
use App\Models\MediaApiCredential;
use App\Models\MediaChannel;
use App\Models\NewsRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewsWorkflowService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'BORRADOR' => ['PENDIENTE', 'CANCELADA'],
        'PENDIENTE' => ['INVESTIGANDO', 'CANCELADA'],
        'INVESTIGANDO' => ['INVESTIGACION_COMPLETA', 'ERROR'],
        'INVESTIGACION_COMPLETA' => ['GENERANDO', 'ERROR'],
        'GENERANDO' => ['COMPLETADA', 'PARCIALMENTE_COMPLETADA', 'ERROR'],
        'ERROR' => ['PENDIENTE', 'INVESTIGANDO', 'GENERANDO'],
        'PARCIALMENTE_COMPLETADA' => ['GENERANDO'],
    ];

    public function createAndDispatch(array $data, User $user, ?MediaApiCredential $credential = null, ?string $idempotencyKey = null, ?string $payloadHash = null): NewsRequest
    {
        return DB::transaction(function () use ($data, $user, $credential, $idempotencyKey, $payloadHash): NewsRequest {
            $channels = MediaChannel::query()
                ->with(['mediaOutlet.editorialProfile.versions'])
                ->whereKey($data['channel_ids'])
                ->lockForUpdate()
                ->get();

            if ($channels->count() !== count(array_unique($data['channel_ids']))) {
                throw ValidationException::withMessages(['channel_ids' => 'Uno o más canales ya no están disponibles.']);
            }

            $urls = collect(preg_split('/\R+/', (string) ($data['initial_urls_text'] ?? '')))
                ->map(fn (string $url): string => trim($url))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $request = NewsRequest::create([
                ...Arr::only($data, ['title', 'description', 'context', 'specific_instructions', 'category', 'priority', 'event_at', 'web_search_enabled', 'research_notes']),
                'requested_by' => $user->id,
                'api_media_outlet_id' => $credential?->media_outlet_id,
                'api_credential_id' => $credential?->id,
                'api_idempotency_key' => $idempotencyKey,
                'api_payload_hash' => $payloadHash,
                'initial_urls' => $urls ?: null,
                'status' => 'PENDIENTE',
                'progress' => 5,
            ]);

            foreach ($channels as $channel) {
                $profile = $channel->mediaOutlet->editorialProfile;
                $profileVersion = $profile?->versions->firstWhere('version', $profile->current_version);
                $request->destinations()->create([
                    'media_outlet_id' => $channel->media_outlet_id,
                    'media_channel_id' => $channel->id,
                    'editorial_profile_version_id' => $profileVersion?->id,
                    'status' => 'PENDIENTE',
                ]);
            }

            $research = $request->researches()->create(['version' => 1, 'status' => 'PENDIENTE']);
            $jobRun = $request->jobRuns()->create([
                'processable_type' => $research->getMorphClass(),
                'processable_id' => $research->id,
                'idempotency_key' => (string) Str::uuid(),
                'operation' => 'INVESTIGACION',
                'status' => 'PENDIENTE',
            ]);
            $request->statusHistory()->create(['user_id' => $user->id, 'to_status' => 'PENDIENTE', 'reason' => 'Solicitud creada e incorporada a la cola.']);

            DB::afterCommit(function () use ($request, $research, $jobRun): void {
                try {
                    RunCentralResearch::dispatch($request->id, $research->id, $jobRun->id);
                } catch (\Throwable $exception) {
                    $request->update(['status' => 'ERROR', 'progress' => 0]);
                    $research->update(['status' => 'ERROR', 'error_message' => 'No fue posible incorporar la investigación a la cola.']);
                    $jobRun->update(['status' => 'ERROR', 'error_message' => 'No fue posible incorporar el trabajo a la cola.', 'completed_at' => now()]);

                    throw $exception;
                }
            });

            return $request;
        });
    }

    public function transition(NewsRequest $request, string $to, ?User $user = null, ?string $reason = null): void
    {
        $from = $request->status;
        if ($from === $to) {
            return;
        }
        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \DomainException("Transición inválida de {$from} a {$to}.");
        }

        $request->update(['status' => $to]);
        $request->statusHistory()->create(['user_id' => $user?->id, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason]);
    }

    public function retry(NewsRequest $request, User $user): void
    {
        DB::transaction(function () use ($request, $user): void {
            $request = NewsRequest::query()->lockForUpdate()->findOrFail($request->id);
            $research = $request->researches()->latest('version')->firstOrFail();

            if ($research->status === 'COMPLETADA') {
                $this->transition($request, 'GENERANDO', $user, 'Reintento de destinos fallidos.');
                $destinations = $request->destinations()->where('status', 'ERROR')->get();
                foreach ($destinations as $destination) {
                    $destination->update(['status' => 'PENDIENTE', 'error_message' => null]);
                    $jobRun = AiJobRun::create([
                        'news_request_id' => $request->id,
                        'processable_type' => $destination->getMorphClass(),
                        'processable_id' => $destination->id,
                        'idempotency_key' => (string) Str::uuid(),
                        'operation' => 'GENERACION',
                        'status' => 'PENDIENTE',
                    ]);
                    DB::afterCommit(fn () => GenerateDestinationContent::dispatch($request->id, $research->id, $destination->id, $jobRun->id));
                }

                return;
            }

            $this->transition($request, 'PENDIENTE', $user, 'Reintento de investigación.');
            $research->update(['status' => 'PENDIENTE', 'error_message' => null]);
            $jobRun = AiJobRun::create([
                'news_request_id' => $request->id,
                'processable_type' => $research->getMorphClass(),
                'processable_id' => $research->id,
                'idempotency_key' => (string) Str::uuid(),
                'operation' => 'INVESTIGACION',
                'status' => 'PENDIENTE',
            ]);
            DB::afterCommit(fn () => RunCentralResearch::dispatch($request->id, $research->id, $jobRun->id));
        });
    }
}
