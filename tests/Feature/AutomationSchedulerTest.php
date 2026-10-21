<?php

namespace Tests\Feature;

use App\Jobs\ExecuteAutomationRun;
use App\Models\AutomationBudget;
use App\Models\AutomationRate;
use App\Models\AutomationRun;
use App\Models\AutomationTask;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Automation\AutomationDispatchService;
use App\Services\Automation\AutomationSchedule;
use App\Services\Automation\AutomationWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekday_noon_in_argentina_is_calculated_in_utc_across_weekend(): void
    {
        $configuration = $this->configuration();
        $next = app(AutomationSchedule::class)->nextOccurrence($configuration, CarbonImmutable::parse('2026-10-23 16:00:00', 'UTC'));

        $this->assertSame('2026-10-26 15:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_two_dispatch_passes_create_only_one_run_for_a_due_time(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        $this->travelTo(CarbonImmutable::parse('2026-10-20 15:01:00', 'UTC'));
        $task = $this->activeTask();
        $task->update(['next_run_at' => now()->subMinute()]);

        app(AutomationDispatchService::class)->dispatchDue();
        app(AutomationDispatchService::class)->dispatchDue();

        $this->assertDatabaseCount('automation_runs', 1);
        Queue::assertPushed(ExecuteAutomationRun::class, 1);
    }

    public function test_pending_run_without_dispatch_marker_is_recovered(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        $task = $this->activeTask();
        $version = $task->currentVersion();
        $quote = $version->quotes()->whereNotNull('accepted_at')->firstOrFail();
        $run = AutomationRun::create(['automation_task_id' => $task->id, 'organization_id' => $task->organization_id, 'automation_task_version_id' => $version->id, 'automation_quote_id' => $quote->id, 'scheduled_for' => now(), 'trigger' => 'PROGRAMADA', 'status' => 'PENDIENTE', 'window_from' => now()->subDay(), 'window_to' => now(), 'idempotency_key' => 'recovery-test']);

        $recovered = app(AutomationDispatchService::class)->recoverUndispatched();

        $this->assertSame(1, $recovered);
        $this->assertSame('DESPACHADA', $run->fresh()->status);
        Queue::assertPushed(ExecuteAutomationRun::class, 1);
    }

    public function test_old_due_time_is_recorded_as_omitted_without_provider_job(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        config(['services.automation.late_tolerance_minutes' => 60]);
        $this->travelTo(CarbonImmutable::parse('2026-10-20 20:00:00', 'UTC'));
        $task = $this->activeTask();
        $task->update(['next_run_at' => now()->subHours(5)]);

        $result = app(AutomationDispatchService::class)->dispatchDue();

        $this->assertSame(1, $result['omitted']);
        $this->assertDatabaseHas('automation_runs', ['automation_task_id' => $task->id, 'status' => 'OMITIDA']);
        Queue::assertNothingPushed();
    }

    public function test_paused_task_does_not_create_scheduled_runs(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        $task = $this->activeTask();
        $task->update(['status' => 'PAUSADA', 'next_run_at' => now()->subMinute()]);

        app(AutomationDispatchService::class)->dispatchDue();

        $this->assertDatabaseCount('automation_runs', 0);
        Queue::assertNothingPushed();
    }

    private function activeTask(): AutomationTask
    {
        config(['services.openai.key' => 'test-key']);
        $role = Role::firstOrCreate(['code' => 'CLIENTE'], ['name' => 'CLIENTE']);
        $organization = Organization::factory()->create(['is_active' => true]);
        $user = User::factory()->for($role)->for($organization)->create();
        AutomationRate::factory()->create();
        AutomationBudget::factory()->for($organization)->create();
        $task = app(AutomationWorkflowService::class)->create([...$this->configuration(), 'domains_text' => 'argentina.gob.ar'], $user, $organization->id);
        app(AutomationWorkflowService::class)->activate($task, $task->quotes()->firstOrFail());

        return $task->fresh();
    }

    private function configuration(): array
    {
        return ['name' => 'Noticias', 'topic' => 'Economía', 'instructions' => '', 'editorial_approach' => '', 'language' => 'es-AR', 'domains' => ['argentina.gob.ar'], 'frequency' => 'SELECTED_DAYS', 'days' => [1, 2, 3, 4, 5], 'time' => '12:00', 'timezone' => 'America/Argentina/Buenos_Aires', 'min_characters' => 700, 'max_characters' => 1000, 'no_news_policy' => 'NO_CONTENT'];
    }
}
