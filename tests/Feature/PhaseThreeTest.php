<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\EditorialProfileService;
use App\Services\MediaCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_can_create_a_media_outlet_and_operation_is_audited(): void
    {
        $root = $this->root();
        $organization = Organization::factory()->create();
        $response = $this->actingAs($root)->post(route('admin.medios.store'), $this->mediaData($organization));
        $media = MediaOutlet::firstOrFail();
        $response->assertRedirect(route('admin.medios.show', $media));
        $this->assertDatabaseHas('media_outlets', ['organization_id' => $organization->id, 'name' => 'Diario Prueba']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREAR', 'module' => 'MEDIOS', 'auditable_id' => $media->id]);
    }

    public function test_client_only_lists_and_views_media_from_its_organization(): void
    {
        $own = Organization::factory()->create();
        $other = Organization::factory()->create();
        $visible = MediaOutlet::factory()->for($own)->create(['name' => 'Medio propio']);
        $hidden = MediaOutlet::factory()->for($other)->create(['name' => 'Medio ajeno']);
        $client = $this->client($own);
        $this->actingAs($client)->get(route('admin.medios.index'))->assertOk()->assertSee($visible->name)->assertDontSee($hidden->name);
        $this->actingAs($client)->get(route('admin.medios.show', $hidden))->assertForbidden();
    }

    public function test_root_can_create_channel_belonging_to_media(): void
    {
        $root = $this->root();
        $media = MediaOutlet::factory()->create();
        $this->actingAs($root)->post(route('admin.medios.canales.store', $media), ['name' => 'Web principal', 'type' => 'WEB', 'url' => 'https://medio.test', 'status' => 'ACTIVO'])->assertRedirect(route('admin.medios.show', $media));
        $this->assertDatabaseHas('media_channels', ['media_outlet_id' => $media->id, 'type' => 'WEB']);
    }

    public function test_channel_from_another_media_cannot_be_modified_through_manipulated_url(): void
    {
        $root = $this->root();
        $media = MediaOutlet::factory()->create();
        $foreign = MediaChannel::factory()->create();
        $this->actingAs($root)->put(route('admin.medios.canales.update', [$media, $foreign]), ['name' => 'Alterado', 'type' => 'WEB', 'status' => 'ACTIVO'])->assertNotFound();
    }

    public function test_credentials_are_hashed_verified_and_revoked(): void
    {
        $root = $this->root();
        $media = MediaOutlet::factory()->create();
        $service = app(MediaCredentialService::class);
        $result = $service->create($media, $root, 'Integración', ['content:read']);
        $credential = $result['credential'];
        $this->assertNotSame($result['plain_text_token'], $credential->token_hash);
        $this->assertSame(hash('sha256', $result['plain_text_token']), $credential->token_hash);
        $this->assertSame($credential->id, $service->verify($result['plain_text_token'])?->id);
        $service->revoke($credential);
        $this->assertNull($service->verify($result['plain_text_token']));
    }

    public function test_generate_token_endpoint_displays_secret_once_without_caching_or_auditing_it(): void
    {
        $root = $this->root();
        $media = MediaOutlet::factory()->create();

        $response = $this->actingAs($root)->post(route('admin.medios.credenciales.store', $media), [
            'name' => 'Portal de demostración',
            'scopes' => ['content:read'],
        ]);

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        preg_match('/value="(nria_[^"]+)"/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $plainTextToken = html_entity_decode($matches[1]);
        $credential = $media->apiCredentials()->firstOrFail();
        $this->assertSame(hash('sha256', $plainTextToken), $credential->token_hash);
        $this->assertNotSame($plainTextToken, $credential->token_hash);
        $this->assertStringNotContainsString($plainTextToken, json_encode(AuditLog::firstOrFail()->toArray(), JSON_THROW_ON_ERROR));

        $this->actingAs($root)->get(route('admin.medios.show', $media))->assertOk()->assertDontSee($plainTextToken);
    }

    public function test_editorial_changes_create_versions_and_restore_creates_another_version(): void
    {
        $root = $this->root();
        $media = MediaOutlet::factory()->create();
        $service = app(EditorialProfileService::class);
        $first = $service->save($media, $root, $this->editorialData('Formal'));
        $service->save($media, $root, $this->editorialData('Directo'));
        $restored = $service->restore($first->fresh(), 1, $root);
        $this->assertSame(3, $restored->current_version);
        $this->assertSame(3, $restored->versions()->count());
        $this->assertSame('Formal', $restored->fresh()->identity['tone']);
    }

    public function test_client_submitted_protected_editorial_fields_are_ignored(): void
    {
        $organization = Organization::factory()->create();
        $client = $this->client($organization);
        $media = MediaOutlet::factory()->for($organization)->create();
        $service = app(EditorialProfileService::class);
        $service->save($media, $this->root(), $this->editorialData('Inicial'));
        $payload = $this->editorialData('Cliente');
        $payload['identity']['focus'] = 'Intento de sobrescritura';
        $this->actingAs($client)->put(route('admin.medios.editorial.update', $media), $payload)->assertRedirect(route('admin.medios.show', $media));
        $this->assertSame('', $media->editorialProfile->fresh()->identity['focus']);
        $this->assertSame('Cliente', $media->editorialProfile->fresh()->identity['tone']);
    }

    private function root(): User
    {
        return User::factory()->for(Role::factory()->create(['code' => 'ROOT']))->create();
    }

    private function client(Organization $organization): User
    {
        return User::factory()->for(Role::factory()->create(['code' => 'CLIENTE']))->for($organization)->create();
    }

    private function mediaData(Organization $organization): array
    {
        return ['organization_id' => $organization->id, 'name' => 'Diario Prueba', 'public_identifier' => 'diario-prueba', 'type' => 'PORTAL_NOTICIAS', 'status' => 'ACTIVO'];
    }

    private function editorialData(string $tone): array
    {
        return ['name' => 'Perfil principal', 'language' => 'es-AR', 'identity' => ['audience' => 'General', 'focus' => '', 'tone' => $tone, 'style' => 'Periodístico', 'preferred_vocabulary' => 'Claro', 'avoid_expressions' => ''], 'production' => ['preferred_length' => '800 palabras', 'title_structure' => 'Informativo', 'summary_style' => 'Breve', 'body_structure' => 'Pirámide invertida', 'use_subtitles' => true, 'quote_preferences' => 'Directas', 'output_format' => 'HTML'], 'sources_config' => ['preferred' => 'Fuentes oficiales', 'restricted' => ''], 'restrictions' => ['preferred_topics' => '', 'excluded_topics' => '', 'undesired_approaches' => '', 'writing_rules' => ''], 'additional_instructions' => '', 'change_note' => 'Prueba'];
    }
}
