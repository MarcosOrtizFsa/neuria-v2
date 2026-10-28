<?php

namespace App\Services\Automation;

use App\Jobs\ExecuteAutomationRun;
use App\Models\AutomationBudget;
use App\Models\AutomationQuote;
use App\Models\AutomationRun;
use App\Models\AutomationTask;
use App\Models\MediaChannel;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AutomationWorkflowService
{
    public function __construct(
        private readonly AutomationConfigurationService $configuration,
        private readonly AutomationQuoteService $quotes,
        private readonly AutomationSchedule $schedule,
    ) {}

    public function create(array $data, User $user, int $organizationId, ?string $originalRequest = null): AutomationTask
    {
        return DB::transaction(function () use ($data, $user, $organizationId, $originalRequest): AutomationTask {
            $configuration = $this->configuration->fromValidated($data);
            $configuration = $this->withDestinationSnapshot($configuration, $organizationId);
            $task = AutomationTask::create([
                'organization_id' => $organizationId,
                'created_by' => $user->id,
                'name' => $configuration['name'],
                'type' => $configuration['type'],
                'status' => 'PENDIENTE_CONFIRMACION',
                'current_version' => 1,
                'original_request' => $originalRequest,
                'timezone' => $configuration['timezone'],
            ]);
            $version = $task->versions()->create([
                'created_by' => $user->id,
                'version' => 1,
                'configuration' => $configuration,
                'configuration_hash' => $this->configuration->hash($configuration),
                'change_note' => 'Configuración inicial',
            ]);
            $this->quotes->quote($version);

            return $task->fresh(['versions', 'quotes.rate']);
        });
    }

    public function update(AutomationTask $task, array $data, User $user): AutomationTask
    {
        return DB::transaction(function () use ($task, $data, $user): AutomationTask {
            $task = AutomationTask::query()->lockForUpdate()->findOrFail($task->id);
            if ($task->status === 'ARCHIVADA') {
                throw new \DomainException('Una tarea archivada no puede editarse.');
            }
            $configuration = $this->configuration->fromValidated($data);
            $configuration = $this->withDestinationSnapshot($configuration, $task->organization_id);
            $nextVersion = $task->current_version + 1;
            $version = $task->versions()->create(['created_by' => $user->id, 'version' => $nextVersion, 'configuration' => $configuration, 'configuration_hash' => $this->configuration->hash($configuration), 'change_note' => 'Configuración editada; requiere nueva confirmación.']);
            $task->update(['name' => $configuration['name'], 'current_version' => $nextVersion, 'timezone' => $configuration['timezone'], 'status' => 'PENDIENTE_CONFIRMACION', 'next_run_at' => null, 'confirmed_at' => null, 'paused_at' => null]);
            $task->runs()->whereIn('status', ['PENDIENTE', 'DESPACHADA'])->where('automation_task_version_id', '!=', $version->id)->update(['status' => 'CANCELADA', 'finished_at' => now(), 'sanitized_error' => 'La configuración fue reemplazada antes de ejecutarse.']);
            $this->quotes->quote($version);

            return $task->fresh(['versions', 'quotes.rate']);
        });
    }

    public function activate(AutomationTask $task, AutomationQuote $quote): void
    {
        DB::transaction(function () use ($task, $quote): void {
            $task = AutomationTask::query()->lockForUpdate()->findOrFail($task->id);
            $quote = AutomationQuote::query()->lockForUpdate()->findOrFail($quote->id);
            if ($quote->automation_task_id !== $task->id || $quote->version->version !== $task->current_version || ($quote->accepted_at === null && $quote->valid_until->isPast())) {
                throw new \DomainException('La cotización ya no es válida para esta configuración.');
            }
            if (! config('services.openai.key')) {
                throw new \DomainException('La credencial OpenAI no está configurada; solo se permiten borradores.');
            }
            $budget = AutomationBudget::query()->where('organization_id', $task->organization_id)->lockForUpdate()->first();
            if (! $budget) {
                throw new \DomainException('La organización no tiene presupuesto de automatizaciones configurado.');
            }
            $activeTasks = AutomationTask::query()->where('organization_id', $task->organization_id)->where('status', 'ACTIVA')->whereKeyNot($task->id)->count();
            if ($activeTasks >= $budget->active_task_limit) {
                throw new \DomainException('Se alcanzó el límite de tareas activas de la organización.');
            }
            $quote->update(['accepted_at' => $quote->accepted_at ?? now()]);
            $quote->version->update(['accepted_at' => $quote->version->accepted_at ?? now()]);
            $task->update(['status' => 'ACTIVA', 'confirmed_at' => now(), 'paused_at' => null, 'next_run_at' => $this->schedule->nextOccurrence($quote->version->configuration, now())]);
        });
    }

    public function pause(AutomationTask $task): void
    {
        $task->update(['status' => 'PAUSADA', 'paused_at' => now(), 'next_run_at' => null]);
    }

    public function archive(AutomationTask $task): void
    {
        DB::transaction(function () use ($task): void {
            $task->update(['status' => 'ARCHIVADA', 'archived_at' => now(), 'next_run_at' => null]);
            $task->runs()->whereIn('status', ['PENDIENTE', 'DESPACHADA'])->update(['status' => 'CANCELADA', 'finished_at' => now(), 'sanitized_error' => 'La tarea fue archivada antes de ejecutarse.']);
        });
    }

    public function runNow(AutomationTask $task, string $requestId): AutomationRun
    {
        return DB::transaction(function () use ($task, $requestId): AutomationRun {
            $task = AutomationTask::query()->lockForUpdate()->findOrFail($task->id);
            if (in_array($task->status, ['BORRADOR', 'PENDIENTE_CONFIRMACION', 'ARCHIVADA', 'BLOQUEADA'], true)) {
                throw new \DomainException('La tarea debe estar confirmada para ejecutarla.');
            }
            $version = $task->currentVersion();
            $quote = $version?->quotes()->whereNotNull('accepted_at')->latest('accepted_at')->first();
            if (! $version || ! $quote) {
                throw new \DomainException('No existe una cotización aceptada para ejecutar esta tarea.');
            }
            $run = AutomationRun::firstOrCreate(['idempotency_key' => 'manual:'.$requestId], [
                'automation_task_id' => $task->id,
                'organization_id' => $task->organization_id,
                'automation_task_version_id' => $version->id,
                'automation_quote_id' => $quote->id,
                'trigger' => 'MANUAL',
                'status' => 'PENDIENTE',
                'window_from' => $this->researchWindowStart($task),
                'window_to' => now(),
            ]);
            if ($run->wasRecentlyCreated) {
                DB::afterCommit(fn () => $this->dispatch($run));
            }

            return $run;
        });
    }

    private function dispatch(AutomationRun $run): void
    {
        ExecuteAutomationRun::dispatch($run->id);
        $run->update(['status' => 'DESPACHADA', 'dispatched_at' => now()]);
    }

    private function researchWindowStart(AutomationTask $task): Carbon
    {
        $lastCompleted = $task->runs()->whereIn('status', ['COMPLETADA', 'SIN_NOVEDADES'])->latest('window_to')->value('window_to');

        return $lastCompleted ? Carbon::parse($lastCompleted)->subMinutes(config('services.automation.overlap_minutes', 15)) : now()->subDay();
    }

    /** @param array<string, mixed> $configuration */
    private function withDestinationSnapshot(array $configuration, int $organizationId): array
    {
        $channel = MediaChannel::query()
            ->with('mediaOutlet.editorialProfile.versions')
            ->whereKey($configuration['media_channel_id'])
            ->where('status', 'ACTIVO')
            ->firstOrFail();

        if ($channel->mediaOutlet->organization_id !== $organizationId || $channel->mediaOutlet->status !== 'ACTIVO') {
            throw new \DomainException('El canal seleccionado no pertenece a la organización o no está activo.');
        }

        $profile = $channel->mediaOutlet->editorialProfile;
        $profileVersion = $profile?->versions->firstWhere('version', $profile->current_version);
        if (! $profileVersion) {
            throw new \DomainException('El medio seleccionado debe tener una línea editorial versionada antes de usar Piloto Automático.');
        }

        return [
            ...$configuration,
            'media_outlet_id' => $channel->media_outlet_id,
            'editorial_profile_version_id' => $profileVersion->id,
            'destination' => 'MEDIA_API',
        ];
    }
}
