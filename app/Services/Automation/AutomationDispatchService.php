<?php

namespace App\Services\Automation;

use App\Jobs\ExecuteAutomationRun;
use App\Models\AutomationRun;
use App\Models\AutomationTask;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AutomationDispatchService
{
    public function __construct(private readonly AutomationSchedule $schedule) {}

    /** @return array{created:int,omitted:int,recovered:int} */
    public function dispatchDue(): array
    {
        $recovered = $this->recoverUndispatched();
        $created = 0;
        $omitted = 0;

        AutomationTask::query()->where('status', 'ACTIVA')->whereNotNull('next_run_at')->where('next_run_at', '<=', now())->orderBy('next_run_at')->limit(100)->pluck('id')->each(function (int $taskId) use (&$created, &$omitted): void {
            $run = DB::transaction(function () use ($taskId): ?AutomationRun {
                $task = AutomationTask::query()->lockForUpdate()->find($taskId);
                if (! $task || $task->status !== 'ACTIVA' || ! $task->next_run_at?->lte(now())) {
                    return null;
                }
                $version = $task->currentVersion();
                $quote = $version?->quotes()->whereNotNull('accepted_at')->latest('accepted_at')->first();
                if (! $version || ! $quote) {
                    $task->update(['status' => 'BLOQUEADA', 'next_run_at' => null]);

                    return null;
                }
                $latestDue = CarbonImmutable::instance($task->next_run_at)->utc();
                $next = $this->schedule->nextOccurrence($version->configuration, $latestDue);
                $skipped = 0;
                while ($next->lte(now())) {
                    $latestDue = $next;
                    $next = $this->schedule->nextOccurrence($version->configuration, $next);
                    $skipped++;
                }
                $task->update(['next_run_at' => $next]);
                $isTooLate = $latestDue->lt(now()->subMinutes(config('services.automation.late_tolerance_minutes', 180)));
                $run = AutomationRun::firstOrCreate(['automation_task_id' => $task->id, 'scheduled_for' => $latestDue, 'trigger' => 'PROGRAMADA'], [
                    'organization_id' => $task->organization_id,
                    'automation_task_version_id' => $version->id,
                    'automation_quote_id' => $quote->id,
                    'status' => $isTooLate ? 'OMITIDA' : 'PENDIENTE',
                    'finished_at' => $isTooLate ? now() : null,
                    'window_from' => $this->researchWindowStart($task),
                    'window_to' => $latestDue,
                    'sanitized_error' => $isTooLate ? 'Vencimiento fuera de la tolerancia; no se consumió proveedor.' : ($skipped > 0 ? "Se omitieron {$skipped} vencimientos anteriores para evitar acumulación." : null),
                    'idempotency_key' => 'scheduled:'.$task->id.':'.$latestDue->format('YmdHis'),
                ]);

                return $run->wasRecentlyCreated ? $run : null;
            });

            if ($run?->status === 'PENDIENTE') {
                $created++;
                $this->dispatch($run);
            } elseif ($run?->status === 'OMITIDA') {
                $omitted++;
            }
        });

        return compact('created', 'omitted', 'recovered');
    }

    public function recoverUndispatched(): int
    {
        $count = 0;
        AutomationRun::query()
            ->where('status', 'DESPACHADA')
            ->where('dispatched_at', '<=', now()->subMinutes(5))
            ->whereNull('started_at')
            ->update(['status' => 'PENDIENTE', 'dispatched_at' => null]);

        AutomationRun::query()->where('status', 'PENDIENTE')->whereNull('dispatched_at')->orderBy('id')->limit(100)->pluck('id')->each(function (int $runId) use (&$count): void {
            $run = AutomationRun::find($runId);
            if ($run && $this->dispatch($run)) {
                $count++;
            }
        });

        return $count;
    }

    private function dispatch(AutomationRun $run): bool
    {
        $claimed = DB::transaction(function () use ($run): bool {
            $locked = AutomationRun::query()->lockForUpdate()->find($run->id);
            if (! $locked || $locked->status !== 'PENDIENTE' || $locked->dispatched_at !== null) {
                return false;
            }
            $locked->update(['status' => 'DESPACHADA', 'dispatched_at' => now()]);

            return true;
        });

        if (! $claimed) {
            return false;
        }

        try {
            ExecuteAutomationRun::dispatch($run->id);
        } catch (\Throwable $exception) {
            AutomationRun::query()
                ->whereKey($run->id)
                ->where('status', 'DESPACHADA')
                ->whereNull('started_at')
                ->update(['status' => 'PENDIENTE', 'dispatched_at' => null]);

            throw $exception;
        }

        return true;
    }

    private function researchWindowStart(AutomationTask $task): CarbonImmutable
    {
        $lastCompleted = $task->runs()->whereIn('status', ['COMPLETADA', 'SIN_NOVEDADES'])->latest('window_to')->value('window_to');

        return $lastCompleted ? CarbonImmutable::parse($lastCompleted)->subMinutes(config('services.automation.overlap_minutes', 15)) : CarbonImmutable::now('UTC')->subDay();
    }
}
