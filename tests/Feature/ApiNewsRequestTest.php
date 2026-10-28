<?php

namespace Tests\Feature;

use App\Jobs\RunCentralResearch;
use App\Models\EditorialProfile;
use App\Models\MediaApiCredential;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiNewsRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_credential_with_request_scope_creates_queued_request_for_its_media(): void
    {
        Queue::fake([RunCentralResearch::class]);
        [$token, $media, $channel] = $this->apiClient(['requests:write']);

        $response = $this->withToken($token)->postJson('/api/v1/requests', [
            'title' => 'Inteligencia artificial en educación',
            'description' => 'Investigar avances recientes y comprobables.',
            'initial_urls' => ['https://8.8.8.8/source'],
        ]);

        $newsRequest = NewsRequest::firstOrFail();
        $response->assertAccepted()
            ->assertJsonPath('data.id', $newsRequest->public_id)
            ->assertJsonPath('data.status', 'PENDIENTE')
            ->assertJsonCount(0, 'data.destinations')
            ->assertHeader('Location', route('api.v1.requests.show', $newsRequest->public_id));
        $this->assertSame($media->id, $newsRequest->destinations()->firstOrFail()->media_outlet_id);
        $this->assertSame(['https://8.8.8.8/source'], $newsRequest->initial_urls);
        Queue::assertPushed(RunCentralResearch::class, fn (RunCentralResearch $job): bool => $job->newsRequestId === $newsRequest->id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREAR_SOLICITUD_API', 'auditable_id' => $newsRequest->id]);
    }

    public function test_credential_without_request_scope_cannot_create_request(): void
    {
        [$token] = $this->apiClient(['content:read']);

        $this->withToken($token)->postJson('/api/v1/requests', ['title' => 'Solicitud'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INSUFFICIENT_SCOPE');

        $this->assertDatabaseCount('news_requests', 0);
    }

    public function test_request_cannot_target_channel_from_another_media(): void
    {
        [$token] = $this->apiClient(['requests:write']);
        [, , $otherChannel] = $this->apiClient(['requests:write']);

        $this->withToken($token)->postJson('/api/v1/requests', [
            'title' => 'Solicitud cruzada',
            'description' => 'Descripción válida para comprobar el aislamiento.',
            'channel_ids' => [$otherChannel->id],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->assertDatabaseCount('news_requests', 0);
    }

    public function test_request_status_is_visible_only_to_its_media(): void
    {
        Queue::fake([RunCentralResearch::class]);
        [$token] = $this->apiClient(['requests:write']);
        [$otherToken] = $this->apiClient(['requests:write']);
        $publicId = $this->withToken($token)->postJson('/api/v1/requests', [
            'title' => 'Solicitud privada',
            'description' => 'Descripción de la solicitud privada.',
        ])->json('data.id');

        $this->withToken($token)->getJson('/api/v1/requests/'.$publicId)
            ->assertOk()
            ->assertJsonPath('data.id', $publicId);
        $this->withToken($otherToken)->getJson('/api/v1/requests/'.$publicId)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_completed_request_exposes_downloadable_content_id(): void
    {
        Queue::fake([RunCentralResearch::class]);
        [$token] = $this->apiClient(['content:read', 'requests:write']);
        $publicId = $this->withToken($token)->postJson('/api/v1/requests', [
            'title' => 'Solicitud completada',
            'description' => 'Contenido que simula el resultado del worker.',
        ])->json('data.id');
        $newsRequest = NewsRequest::where('public_id', $publicId)->firstOrFail();
        $destination = $newsRequest->destinations()->firstOrFail();
        $research = $newsRequest->researches()->firstOrFail();
        $draft = $destination->draft()->create([
            'news_research_id' => $research->id,
            'editorial_profile_version_id' => $destination->editorial_profile_version_id,
            'title' => 'Contenido disponible',
            'summary' => 'Resumen',
            'body' => 'Cuerpo editorial',
            'tags' => [],
            'source_references' => [],
            'editorial_warnings' => [],
            'status' => 'DISPONIBLE',
            'generated_at' => now(),
            'available_at' => now(),
        ]);
        $destination->update(['status' => 'COMPLETADA']);
        $newsRequest->update(['status' => 'COMPLETADA', 'progress' => 100]);

        $this->withToken($token)->getJson('/api/v1/requests/'.$publicId)
            ->assertOk()
            ->assertJsonPath('data.progress', 100)
            ->assertJsonPath('data.destinations.0.content_id', $draft->public_id)
            ->assertJsonPath('data.destinations.0.content_status', 'AVAILABLE');
    }

    public function test_idempotency_key_replays_same_request_without_dispatching_duplicate(): void
    {
        Queue::fake([RunCentralResearch::class]);
        [$token] = $this->apiClient(['requests:write']);
        $payload = ['title' => 'Solicitud idempotente', 'description' => 'Una única producción editorial.'];

        $first = $this->withToken($token)->withHeader('Idempotency-Key', 'isfd-request-0001')->postJson('/api/v1/requests', $payload);
        $second = $this->withToken($token)->withHeader('Idempotency-Key', 'isfd-request-0001')->postJson('/api/v1/requests', $payload);

        $first->assertAccepted();
        $second->assertAccepted()->assertHeader('X-Idempotent-Replay', 'true')->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('news_requests', 1);
        Queue::assertPushed(RunCentralResearch::class, 1);
    }

    public function test_idempotency_key_cannot_be_reused_with_different_payload(): void
    {
        Queue::fake([RunCentralResearch::class]);
        [$token] = $this->apiClient(['requests:write']);
        $headers = ['Idempotency-Key' => 'isfd-request-0002'];
        $this->withToken($token)->withHeaders($headers)->postJson('/api/v1/requests', [
            'title' => 'Solicitud original',
            'description' => 'Contenido original.',
        ])->assertAccepted();

        $this->withToken($token)->withHeaders($headers)->postJson('/api/v1/requests', [
            'title' => 'Solicitud diferente',
            'description' => 'Contenido diferente.',
        ])->assertStatus(409)->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REUSED');

        $this->assertDatabaseCount('news_requests', 1);
    }

    public function test_private_initial_url_is_rejected_to_prevent_ssrf(): void
    {
        [$token] = $this->apiClient(['requests:write']);

        $this->withToken($token)->postJson('/api/v1/requests', [
            'title' => 'Solicitud insegura',
            'description' => 'Intento de acceso a una dirección interna.',
            'initial_urls' => ['http://127.0.0.1/private'],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->assertDatabaseCount('news_requests', 0);
    }

    public function test_me_returns_scopes_and_channels_for_request_client(): void
    {
        [$token, , $channel] = $this->apiClient(['content:read', 'requests:write']);

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.credential.scopes.0', 'content:read')
            ->assertJsonPath('data.credential.scopes.1', 'requests:write')
            ->assertJsonPath('data.media.channels.0.id', $channel->id)
            ->assertJsonPath('data.media.channels.0.status', 'ACTIVO');
    }

    private function apiClient(array $scopes): array
    {
        $role = Role::firstOrCreate(['code' => 'ROOT'], ['name' => 'ROOT']);
        $root = User::factory()->for($role)->create();
        $media = MediaOutlet::factory()->create(['status' => 'ACTIVO']);
        $channel = MediaChannel::factory()->for($media)->create(['status' => 'ACTIVO']);
        $profile = EditorialProfile::create([
            'media_outlet_id' => $media->id,
            'name' => 'Editorial de prueba',
            'language' => 'es-AR',
            'identity' => ['tone' => 'claro'],
            'production' => [],
            'sources_config' => [],
            'restrictions' => [],
            'current_version' => 1,
        ]);
        $profile->versions()->create(['user_id' => $root->id, 'version' => 1, 'configuration' => ['identity' => ['tone' => 'claro']]]);
        $token = 'nria_'.fake()->lexify('????????????????').'.'.fake()->sha256();
        MediaApiCredential::create([
            'media_outlet_id' => $media->id,
            'created_by' => $root->id,
            'name' => 'Cliente API',
            'public_id' => 'nria_'.fake()->unique()->lexify('????????????????'),
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 20),
            'scopes' => $scopes,
        ]);

        return [$token, $media, $channel, $root];
    }
}
