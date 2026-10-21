<?php

namespace Tests\Feature;

use App\Models\EditorialProfile;
use App\Models\MediaApiCredential;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseSixApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credential_receives_only_its_available_content_and_usage_is_logged(): void
    {
        [$token, $draft] = $this->availableContent();
        [, $otherDraft] = $this->availableContent();

        $response = $this->withToken($token)->getJson('/api/v1/contents');

        $response->assertOk()->assertJsonPath('data.0.id', $draft->public_id)->assertJsonCount(1, 'data');
        $this->assertDatabaseMissing('api_usage_logs', ['records_returned' => 2]);
        $this->assertDatabaseHas('api_usage_logs', ['endpoint' => 'api.v1.contents.index', 'status_code' => 200, 'records_returned' => 1]);
        $response = $this->withToken($token)->getJson('/api/v1/contents/'.$otherDraft->public_id);
        $response->assertNotFound();
    }

    public function test_missing_revoked_or_insufficient_scope_credential_is_rejected(): void
    {
        [$token, , $credential] = $this->availableContent();
        $this->getJson('/api/v1/contents')->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHENTICATED');
        $credential->update(['revoked_at' => now()]);
        $this->withToken($token)->getJson('/api/v1/contents')->assertUnauthorized();

        [$otherToken, , $otherCredential] = $this->availableContent();
        $otherCredential->update(['scopes' => []]);
        $this->withToken($otherToken)->getJson('/api/v1/contents')->assertForbidden()->assertJsonPath('error.code', 'INSUFFICIENT_SCOPE');
    }

    public function test_suspension_and_reactivation_are_exposed_as_incremental_changes(): void
    {
        [$token, $draft, , $root] = $this->availableContent();
        app(ContentAvailabilityService::class)->suspend($draft, $root, 'Verificación');
        app(ContentAvailabilityService::class)->reactivate($draft->fresh(), $root, 'Resuelto');

        $response = $this->withToken($token)->getJson('/api/v1/changes');

        $response->assertOk()->assertJsonPath('data.0.type', 'CONTENT_AVAILABLE')->assertJsonPath('data.1.type', 'CONTENT_SUSPENDED')->assertJsonPath('data.2.type', 'CONTENT_REACTIVATED');
        $this->assertNotNull($response->json('meta.next_cursor'));
        $this->withToken($token)->getJson('/api/v1/changes?cursor='.urlencode($response->json('meta.next_cursor')))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_cursor_cannot_be_reused_by_another_media(): void
    {
        [$token] = $this->availableContent();
        [$otherToken] = $this->availableContent();
        $cursor = $this->withToken($token)->getJson('/api/v1/contents')->json('meta.next_cursor');

        $this->withToken($otherToken)->getJson('/api/v1/contents?cursor='.urlencode($cursor))->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_ERROR')->assertJsonStructure(['error' => ['details' => ['cursor']]]);
    }

    public function test_me_does_not_expose_token_hash_or_secret(): void
    {
        [$token] = $this->availableContent();

        $response = $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $this->assertArrayNotHasKey('token_hash', $response->json('data.credential'));
        $this->assertStringNotContainsString($token, $response->getContent());
    }

    public function test_requests_are_rate_limited_per_credential_and_endpoint(): void
    {
        config(['services.media_api.rate_limit_per_minute' => 1]);
        [$token] = $this->availableContent();

        $this->withToken($token)->getJson('/api/v1/me')->assertOk();
        $this->withToken($token)->getJson('/api/v1/me')->assertTooManyRequests()->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED');
        $this->assertDatabaseHas('api_usage_logs', ['endpoint' => 'api.v1.me', 'status_code' => 429, 'result' => 'RATE_LIMITED']);
    }

    private function availableContent(): array
    {
        $role = Role::firstOrCreate(['code' => 'ROOT'], ['name' => 'ROOT']);
        $root = User::factory()->for($role)->create();
        $media = MediaOutlet::factory()->create(['status' => 'ACTIVO']);
        $channel = MediaChannel::factory()->for($media)->create(['status' => 'ACTIVO']);
        $profile = EditorialProfile::create(['media_outlet_id' => $media->id, 'name' => 'Perfil', 'language' => 'es-AR', 'identity' => [], 'production' => [], 'sources_config' => [], 'restrictions' => [], 'current_version' => 1]);
        $version = $profile->versions()->create(['user_id' => $root->id, 'version' => 1, 'configuration' => []]);
        $newsRequest = NewsRequest::create(['requested_by' => $root->id, 'title' => 'Solicitud', 'priority' => 'NORMAL', 'web_search_enabled' => true, 'status' => 'GENERANDO']);
        $destination = $newsRequest->destinations()->create(['media_outlet_id' => $media->id, 'media_channel_id' => $channel->id, 'editorial_profile_version_id' => $version->id, 'status' => 'GENERANDO']);
        $research = $newsRequest->researches()->create(['version' => 1, 'status' => 'COMPLETADA', 'result' => ['summary' => 'Investigación']]);
        $research->sources()->create(['title' => 'Fuente', 'url' => 'https://example.com/'.fake()->uuid(), 'url_hash' => fake()->sha256(), 'consulted_at' => now()]);
        $draft = $destination->draft()->create(['news_research_id' => $research->id, 'editorial_profile_version_id' => $version->id, 'title' => 'Nota '.fake()->uuid(), 'summary' => 'Resumen', 'body' => 'Cuerpo', 'tags' => ['IA'], 'source_references' => [], 'editorial_warnings' => [], 'status' => 'GENERANDO', 'generated_at' => now()]);
        app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft);
        $token = 'nria_test_'.fake()->uuid().'.'.fake()->sha256();
        $credential = MediaApiCredential::create(['media_outlet_id' => $media->id, 'created_by' => $root->id, 'name' => 'Sincronizador', 'public_id' => 'nria_'.fake()->unique()->lexify('????????????????'), 'token_hash' => hash('sha256', $token), 'token_prefix' => substr($token, 0, 20), 'scopes' => ['content:read']]);

        return [$token, $draft->fresh(), $credential, $root];
    }
}
