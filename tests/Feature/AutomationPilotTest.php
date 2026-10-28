<?php

namespace Tests\Feature;

use App\Contracts\AutomationProvider;
use App\Jobs\ExecuteAutomationRun;
use App\Models\AutomationBudget;
use App\Models\AutomationRate;
use App\Models\AutomationTask;
use App\Models\EditorialProfile;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Automation\AutomationExecutionService;
use App\Services\Automation\AutomationHandlerRegistry;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationPilotTest extends TestCase
{
    use RefreshDatabase;

    private int $mediaChannelId;

    public function test_client_creates_a_draft_and_must_confirm_quote_before_activation(): void
    {
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);

        $response = $this->actingAs($client)->post(route('admin.piloto.store'), $this->formData());

        $task = AutomationTask::firstOrFail();
        $response->assertRedirect(route('admin.piloto.show', $task));
        $this->assertSame('PENDIENTE_CONFIRMACION', $task->status);
        $this->assertNull($task->next_run_at);
        $this->assertDatabaseCount('automation_quotes', 1);
        $quote = $task->quotes()->firstOrFail();
        $this->actingAs($client)->post(route('admin.piloto.activate', $task), ['quote_id' => $quote->id, 'accept_terms' => 1])->assertRedirect();
        $this->assertSame('ACTIVA', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->next_run_at);
    }

    public function test_client_cannot_view_or_change_another_organizations_task(): void
    {
        [$owner, $organization] = $this->client();
        [, $otherOrganization] = $this->client();
        $this->commercialSetup($organization);
        $task = app(AutomationWorkflowService::class)->create($this->formData($organization), $owner, $organization->id);
        $otherClient = User::where('organization_id', $otherOrganization->id)->firstOrFail();

        $this->actingAs($otherClient)->get(route('admin.piloto.show', $task))->assertNotFound();
        $this->actingAs($otherClient)->put(route('admin.piloto.update', $task), $this->formData())->assertNotFound();
    }

    public function test_editing_creates_an_immutable_version_and_requires_confirmation_again(): void
    {
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = app(AutomationWorkflowService::class)->create($this->formData(), $client, $organization->id);
        app(AutomationWorkflowService::class)->activate($task, $task->quotes()->firstOrFail());
        $oldTopic = $task->versions()->firstOrFail()->configuration['topic'];

        $this->actingAs($client)->put(route('admin.piloto.update', $task), [...$this->formData(), 'topic' => 'Nuevo tema económico'])->assertRedirect();

        $this->assertSame(2, $task->fresh()->current_version);
        $this->assertSame('PENDIENTE_CONFIRMACION', $task->fresh()->status);
        $this->assertSame($oldTopic, $task->versions()->where('version', 1)->firstOrFail()->configuration['topic']);
        $this->assertSame('Nuevo tema económico', $task->versions()->where('version', 2)->firstOrFail()->configuration['topic']);
    }

    public function test_manual_double_click_creates_and_dispatches_only_one_run(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $payload = ['request_id' => '3a648c47-32bf-4ebf-970f-e3536ba03d5e', 'accept_charge' => 1];

        $this->actingAs($client)->post(route('admin.piloto.run-now', $task), $payload)->assertRedirect();
        $this->actingAs($client)->post(route('admin.piloto.run-now', $task), $payload)->assertRedirect();

        $this->assertDatabaseCount('automation_runs', 1);
        Queue::assertPushed(ExecuteAutomationRun::class, 1);
    }

    public function test_valid_result_is_saved_and_commercial_charge_is_settled_once(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $run = app(AutomationWorkflowService::class)->runNow($task, '9df64fee-ea02-4987-8137-a4243e804ca9');
        $this->fakeProvider('CONTENT', str_repeat('a', 750));

        app(AutomationExecutionService::class)->execute($run->id);
        app(AutomationExecutionService::class)->execute($run->id);

        $this->assertSame('COMPLETADA', $run->fresh()->status);
        $this->assertDatabaseHas('automation_sources', ['url' => 'https://argentina.gob.ar/noticia']);
        $this->assertDatabaseHas('generated_drafts', ['status' => 'DISPONIBLE', 'title' => 'Nota económica']);
        $this->assertDatabaseHas('news_requests', ['status' => 'COMPLETADA', 'progress' => 100]);
        $this->assertNotNull($run->fresh()->result?->generated_draft_id);
        $this->assertDatabaseCount('automation_ledger_entries', 2);
        $this->assertDatabaseHas('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'LIQUIDACION']);
    }

    public function test_no_news_releases_reservation_and_does_not_charge_client(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $run = app(AutomationWorkflowService::class)->runNow($task, '101d0da4-bb95-4900-8127-1bb01932c1d4');
        $this->fakeProvider('NO_NEWS', '');

        app(AutomationExecutionService::class)->execute($run->id);

        $this->assertSame('SIN_NOVEDADES', $run->fresh()->status);
        $this->assertDatabaseHas('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'LIBERACION']);
        $this->assertDatabaseMissing('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'LIQUIDACION']);
    }

    public function test_out_of_domain_sources_make_result_invalid_and_prevent_charge(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $run = app(AutomationWorkflowService::class)->runNow($task, '4c458c66-e645-442d-977f-c12e22235c58');
        $this->fakeProvider('CONTENT', str_repeat('a', 750), 'https://evil.example/noticia');

        app(AutomationExecutionService::class)->execute($run->id);

        $this->assertSame('INVALIDA', $run->fresh()->status);
        $this->assertDatabaseCount('automation_sources', 0);
        $this->assertDatabaseMissing('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'LIQUIDACION']);
    }

    public function test_insufficient_budget_blocks_before_calling_provider(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization, 0);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $run = app(AutomationWorkflowService::class)->runNow($task, '622134e8-86a4-4100-a7b2-271364dd32af');
        $provider = \Mockery::mock(AutomationProvider::class);
        $provider->shouldNotReceive('generate');
        $this->app->instance(AutomationProvider::class, $provider);

        app(AutomationExecutionService::class)->execute($run->id);

        $this->assertSame('BLOQUEADA', $run->fresh()->status);
    }

    public function test_interpretation_with_missing_domain_does_not_create_a_task(): void
    {
        [$client] = $this->client();
        $provider = \Mockery::mock(AutomationProvider::class);
        $provider->shouldReceive('interpret')->once()->andReturn([...$this->formData(), 'capability_supported' => true, 'missing_fields' => ['domains'], 'domains' => [], 'destination' => 'MEDIA_API']);
        $this->app->instance(AutomationProvider::class, $provider);

        $this->actingAs($client)->post(route('admin.piloto.interpret'), ['request_text' => 'Buscá noticias todos los días en ese medio y redactá una nota.', 'media_channel_id' => $this->mediaChannelId])->assertSessionHasErrors('request_text');

        $this->assertDatabaseCount('automation_tasks', 0);
    }

    public function test_short_result_is_invalid_and_releases_commercial_reservation(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $run = app(AutomationWorkflowService::class)->runNow($task, 'b97c8a31-e34e-4732-b2ef-f20d93014ea3');
        $this->fakeProvider('CONTENT', 'Demasiado breve.');

        app(AutomationExecutionService::class)->execute($run->id);

        $this->assertSame('INVALIDA', $run->fresh()->status);
        $this->assertDatabaseHas('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'LIBERACION']);
    }

    public function test_ambiguous_timeout_is_left_for_reconciliation_without_duplicate_charge(): void
    {
        Queue::fake([ExecuteAutomationRun::class]);
        [$client, $organization] = $this->client();
        $this->commercialSetup($organization);
        config(['services.openai.key' => 'test-key']);
        $task = $this->activeTask($client, $organization);
        $run = app(AutomationWorkflowService::class)->runNow($task, '79c2eaf8-009b-439b-826a-55a998b4c097');
        $provider = \Mockery::mock(AutomationProvider::class);
        $provider->shouldReceive('generate')->once()->andThrow(new ConnectionException('Timeout ambiguo'));
        $this->app->instance(AutomationProvider::class, $provider);
        $this->app->forgetInstance(AutomationHandlerRegistry::class);

        app(AutomationExecutionService::class)->execute($run->id);

        $this->assertSame('ESTADO_DESCONOCIDO', $run->fresh()->status);
        $this->assertDatabaseHas('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'RESERVA']);
        $this->assertDatabaseMissing('automation_ledger_entries', ['automation_run_id' => $run->id, 'type' => 'LIQUIDACION']);
    }

    private function client(): array
    {
        $role = Role::firstOrCreate(['code' => 'CLIENTE'], ['name' => 'CLIENTE']);
        $organization = Organization::factory()->create(['is_active' => true]);
        $client = User::factory()->for($role)->for($organization)->create(['is_active' => true]);
        $this->createDestination($organization, $client);

        return [$client, $organization];
    }

    private function commercialSetup(Organization $organization, int $monthlyLimit = 10000000): void
    {
        AutomationRate::factory()->create(['code' => 'TEST_'.fake()->unique()->numerify('####')]);
        AutomationBudget::factory()->for($organization)->create(['monthly_limit_minor' => $monthlyLimit]);
    }

    private function activeTask(User $client, Organization $organization): AutomationTask
    {
        $task = app(AutomationWorkflowService::class)->create($this->formData(), $client, $organization->id);
        app(AutomationWorkflowService::class)->activate($task, $task->quotes()->firstOrFail());

        return $task->fresh();
    }

    private function formData(?Organization $organization = null): array
    {
        $channelId = $organization
            ? MediaChannel::query()->whereHas('mediaOutlet', fn ($query) => $query->where('organization_id', $organization->id))->value('id')
            : $this->mediaChannelId;

        return ['media_channel_id' => $channelId, 'name' => 'Economía al mediodía', 'topic' => 'Noticias sobre economía argentina', 'instructions' => 'Priorizar novedades de las últimas horas.', 'editorial_approach' => 'Periodismo especializado y mirada crítica.', 'language' => 'es-AR', 'domains_text' => "argentina.gob.ar\nindec.gob.ar", 'domains' => ['argentina.gob.ar', 'indec.gob.ar'], 'frequency' => 'SELECTED_DAYS', 'days' => [1, 2, 3, 4, 5], 'time' => '12:00', 'timezone' => 'America/Argentina/Buenos_Aires', 'min_characters' => 700, 'max_characters' => 1000, 'no_news_policy' => 'NO_CONTENT'];
    }

    private function createDestination(Organization $organization, User $user): void
    {
        $media = MediaOutlet::factory()->for($organization)->create(['status' => 'ACTIVO']);
        $channel = MediaChannel::factory()->for($media)->create(['status' => 'ACTIVO']);
        $profile = EditorialProfile::create(['media_outlet_id' => $media->id, 'name' => 'Perfil de prueba', 'language' => 'es-AR', 'identity' => [], 'production' => [], 'sources_config' => [], 'restrictions' => [], 'current_version' => 1]);
        $profile->versions()->create(['user_id' => $user->id, 'version' => 1, 'configuration' => ['identity' => ['tone' => 'claro']]]);
        $this->mediaChannelId = $channel->id;
    }

    private function fakeProvider(string $status, string $body, string $url = 'https://argentina.gob.ar/noticia'): void
    {
        $provider = \Mockery::mock(AutomationProvider::class);
        $provider->shouldReceive('generate')->once()->andReturn([
            'status' => $status,
            'title' => $status === 'CONTENT' ? 'Nota económica' : null,
            'body' => $body ?: null,
            'warnings' => [],
            'sources' => $status === 'CONTENT' ? [['title' => 'Fuente', 'url' => $url, 'publisher' => 'Portal', 'published_at' => now()->toIso8601String(), 'citation' => 'Dato citado', 'publication_date_verified' => true]] : [],
            'provider' => ['response_id' => 'resp_test', 'model' => 'gpt-test'],
            'usage' => [['attempt' => 1, 'operation' => 'INVESTIGACION_Y_REDACCION', 'model' => 'gpt-test', 'provider_response_id' => 'resp_test', 'input_tokens' => 10, 'output_tokens' => 20, 'total_tokens' => 30, 'tool_calls' => 1, 'technical_cost_minor' => null, 'currency' => null, 'cost_condition' => 'NO_CONCILIADA', 'status' => 'COMPLETADA']],
        ]);
        $this->app->instance(AutomationProvider::class, $provider);
        $this->app->forgetInstance(AutomationHandlerRegistry::class);
    }
}
