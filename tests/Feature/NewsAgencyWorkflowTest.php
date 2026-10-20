<?php

namespace Tests\Feature;

use App\Jobs\GenerateDestinationContent;
use App\Jobs\RunCentralResearch;
use App\Models\EditorialProfile;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentAvailabilityService;
use App\Services\NewsWorkflowService;
use App\Services\OpenAIContentGenerationService;
use App\Services\OpenAIResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewsAgencyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_creates_request_and_dispatches_one_central_research(): void
    {
        Queue::fake([RunCentralResearch::class]);
        [$root, $channel] = $this->destination();

        $response = $this->actingAs($root)->post(route('admin.agencia.store'), $this->requestData($channel));

        $newsRequest = NewsRequest::firstOrFail();
        $response->assertRedirect(route('admin.agencia.show', $newsRequest));
        $this->assertSame('PENDIENTE', $newsRequest->status);
        $this->assertSame(1, $newsRequest->destinations()->count());
        $this->assertSame(1, $newsRequest->researches()->count());
        Queue::assertPushed(RunCentralResearch::class, fn (RunCentralResearch $job): bool => $job->newsRequestId === $newsRequest->id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREAR_SOLICITUD', 'auditable_id' => $newsRequest->id]);
    }

    public function test_client_cannot_access_internal_news_agency(): void
    {
        $organization = Organization::factory()->create();
        $client = User::factory()->for(Role::factory()->create(['code' => 'CLIENTE']))->for($organization)->create();

        $this->actingAs($client)->get(route('admin.agencia.index'))->assertForbidden();
    }

    public function test_jobs_store_research_sources_usage_and_pending_review_draft(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence('https://api.openai.com/v1/responses')
            ->push($this->openAIResponse('resp_research', $this->researchData()))
            ->push($this->openAIResponse('resp_generation', $this->draftData()));
        Queue::fake([RunCentralResearch::class, GenerateDestinationContent::class]);
        [$root, $channel] = $this->destination();
        $this->actingAs($root)->post(route('admin.agencia.store'), $this->requestData($channel));
        $request = NewsRequest::with(['researches', 'jobRuns'])->firstOrFail();
        $researchJob = new RunCentralResearch($request->id, $request->researches->first()->id, $request->jobRuns->first()->id);

        $researchJob->handle(app(OpenAIResearchService::class), app(NewsWorkflowService::class));

        $request->refresh()->load(['destinations', 'researches.sources', 'jobRuns']);
        $this->assertSame('GENERANDO', $request->status);
        $this->assertSame('COMPLETADA', $request->researches->first()->status);
        $this->assertSame(1, $request->researches->first()->sources()->count());
        $generationRun = $request->jobRuns->firstWhere('operation', 'GENERACION');
        $generationJob = new GenerateDestinationContent($request->id, $request->researches->first()->id, $request->destinations->first()->id, $generationRun->id);

        $generationJob->handle(app(OpenAIContentGenerationService::class), app(NewsWorkflowService::class), app(ContentAvailabilityService::class));

        $request->refresh();
        $this->assertSame('COMPLETADA', $request->status);
        $this->assertDatabaseHas('generated_drafts', ['news_request_destination_id' => $request->destinations()->first()->id, 'status' => 'DISPONIBLE']);
        $this->assertSame(2, $request->usages()->count());
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $sent): bool => $sent->url() === 'https://api.openai.com/v1/responses' && $sent->hasHeader('Authorization') && ! str_contains($sent->body(), 'nria_'));
    }

    private function destination(): array
    {
        $root = User::factory()->for(Role::factory()->create(['code' => 'ROOT']))->create();
        $media = MediaOutlet::factory()->create(['status' => 'ACTIVO']);
        $channel = MediaChannel::factory()->for($media)->create(['status' => 'ACTIVO']);
        $profile = EditorialProfile::create(['media_outlet_id' => $media->id, 'name' => 'Editorial de prueba', 'language' => 'es-AR', 'identity' => ['tone' => 'claro'], 'production' => [], 'sources_config' => [], 'restrictions' => [], 'current_version' => 1]);
        $profile->versions()->create(['user_id' => $root->id, 'version' => 1, 'configuration' => ['identity' => ['tone' => 'claro']]]);

        return [$root, $channel];
    }

    private function requestData(MediaChannel $channel): array
    {
        return ['title' => 'Tecnología educativa', 'description' => 'Investigar una novedad comprobable.', 'priority' => 'NORMAL', 'web_search_enabled' => '1', 'channel_ids' => [$channel->id]];
    }

    private function openAIResponse(string $id, array $data): array
    {
        return ['id' => $id, 'model' => 'gpt-test', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($data, JSON_THROW_ON_ERROR)]]]], 'usage' => ['input_tokens' => 100, 'output_tokens' => 50, 'total_tokens' => 150]];
    }

    private function researchData(): array
    {
        return ['summary' => 'Resumen comprobado.', 'confirmed_facts' => ['Hecho confirmado'], 'unconfirmed_facts' => [], 'timeline' => [], 'protagonists' => [], 'context' => 'Contexto', 'sources' => [['title' => 'Fuente oficial', 'url' => 'https://example.com/fuente', 'publisher' => 'Institución', 'relevant_summary' => 'Respaldo']], 'warnings' => [], 'information_gaps' => []];
    }

    private function draftData(): array
    {
        return ['title' => 'Título generado', 'summary' => 'Bajada', 'body' => 'Cuerpo seguro', 'category' => 'Educación', 'tags' => ['IA'], 'source_references' => ['https://example.com/fuente'], 'editorial_warnings' => []];
    }
}
