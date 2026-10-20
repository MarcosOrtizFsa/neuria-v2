<?php

namespace Tests\Feature;

use App\Models\EditorialProfile;
use App\Models\MediaApiCredential;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentAvailabilityService;
use App\Services\ContentDistributionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_technical_validation_makes_valid_generated_content_available(): void
    {
        [$root, $draft] = $this->draft();

        $available = app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft);

        $this->assertTrue($available);
        $this->assertSame('DISPONIBLE', $draft->fresh()->status);
        $this->assertNotNull($draft->fresh()->available_at);
        $this->assertNotNull($draft->fresh()->public_id);
        $this->assertDatabaseHas('content_status_histories', ['generated_draft_id' => $draft->id, 'to_status' => 'DISPONIBLE', 'user_id' => null]);
    }

    public function test_missing_web_sources_blocks_automatic_availability(): void
    {
        [, $draft] = $this->draft(withSource: false);

        $available = app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft);

        $this->assertFalse($available);
        $this->assertSame('ERROR', $draft->fresh()->status);
        $this->assertStringContainsString('fuentes', $draft->fresh()->technical_error);
    }

    public function test_authorized_user_can_suspend_reactivate_and_permanently_retire_content(): void
    {
        [$root, $draft] = $this->draft();
        app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft);

        $this->actingAs($root)->post(route('admin.contenidos.suspend', $draft), ['reason' => 'Control técnico'])->assertRedirect();
        $this->assertSame('SUSPENDIDO', $draft->fresh()->status);
        $this->actingAs($root)->post(route('admin.contenidos.reactivate', $draft), ['reason' => 'Control resuelto'])->assertRedirect();
        $this->assertSame('DISPONIBLE', $draft->fresh()->status);
        $this->actingAs($root)->post(route('admin.contenidos.retire', $draft), ['reason' => 'Retiro definitivo'])->assertRedirect();
        $this->assertSame('RETIRADO', $draft->fresh()->status);
        $this->assertFalse(app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft->fresh()));
        $this->assertSame('RETIRADO', $draft->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'RETIRAR_CONTENIDO', 'auditable_id' => $draft->id]);
    }

    public function test_client_cannot_see_content_from_another_organization(): void
    {
        [, $draft] = $this->draft();
        app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft);
        $otherOrganization = Organization::factory()->create();
        $clientRole = Role::firstOrCreate(['code' => 'CLIENTE'], ['name' => 'CLIENTE']);
        $client = User::factory()->for($clientRole)->for($otherOrganization)->create();

        $this->actingAs($client)->get(route('admin.contenidos.show', $draft))->assertNotFound();
        $this->actingAs($client)->get(route('admin.contenidos.index'))->assertOk()->assertDontSee($draft->title);
    }

    public function test_client_cannot_change_availability_even_for_own_content(): void
    {
        [, $draft, $media] = $this->draft();
        app(ContentAvailabilityService::class)->validateAndMakeAvailable($draft);
        $clientRole = Role::firstOrCreate(['code' => 'CLIENTE'], ['name' => 'CLIENTE']);
        $client = User::factory()->for($clientRole)->for(Organization::findOrFail($media->organization_id))->create();

        $this->actingAs($client)->post(route('admin.contenidos.suspend', $draft))->assertForbidden();

        $this->assertSame('DISPONIBLE', $draft->fresh()->status);
    }

    public function test_later_editorial_changes_do_not_modify_content_version_reference(): void
    {
        [$root, $draft, $media] = $this->draft();
        $originalVersionId = $draft->editorial_profile_version_id;
        $profile = EditorialProfile::where('media_outlet_id', $media->id)->firstOrFail();
        $profile->versions()->create(['user_id' => $root->id, 'version' => 2, 'configuration' => ['identity' => ['tone' => 'nuevo']]]);
        $profile->update(['current_version' => 2]);

        $this->assertSame($originalVersionId, $draft->fresh()->editorial_profile_version_id);
    }

    public function test_distribution_query_returns_only_available_content_for_credential_media(): void
    {
        [$root, $ownDraft, $ownMedia] = $this->draft();
        app(ContentAvailabilityService::class)->validateAndMakeAvailable($ownDraft);
        [, $otherDraft] = $this->draft();
        app(ContentAvailabilityService::class)->validateAndMakeAvailable($otherDraft);
        $credential = MediaApiCredential::create(['media_outlet_id' => $ownMedia->id, 'created_by' => $root->id, 'name' => 'API prueba', 'public_id' => 'nria_test', 'token_hash' => hash('sha256', 'secret'), 'token_prefix' => 'nria_test', 'scopes' => ['content:read']]);

        $results = app(ContentDistributionQuery::class)->availableFor($credential)->get();

        $this->assertCount(1, $results);
        $this->assertSame($ownDraft->id, $results->first()->id);
    }

    private function draft(bool $withSource = true): array
    {
        $rootRole = Role::firstOrCreate(['code' => 'ROOT'], ['name' => 'ROOT']);
        $root = User::factory()->for($rootRole)->create();
        $media = MediaOutlet::factory()->create(['status' => 'ACTIVO']);
        $channel = MediaChannel::factory()->for($media)->create(['status' => 'ACTIVO']);
        $profile = EditorialProfile::create(['media_outlet_id' => $media->id, 'name' => 'Perfil', 'language' => 'es-AR', 'identity' => [], 'production' => [], 'sources_config' => [], 'restrictions' => [], 'current_version' => 1]);
        $version = $profile->versions()->create(['user_id' => $root->id, 'version' => 1, 'configuration' => ['identity' => ['tone' => 'claro']]]);
        $request = NewsRequest::create(['requested_by' => $root->id, 'title' => 'Solicitud', 'priority' => 'NORMAL', 'web_search_enabled' => true, 'status' => 'GENERANDO']);
        $destination = $request->destinations()->create(['media_outlet_id' => $media->id, 'media_channel_id' => $channel->id, 'editorial_profile_version_id' => $version->id, 'status' => 'GENERANDO']);
        $research = $request->researches()->create(['version' => 1, 'status' => 'COMPLETADA', 'result' => ['summary' => 'Investigación']]);
        if ($withSource) {
            $research->sources()->create(['title' => 'Fuente', 'url' => 'https://example.com/source', 'url_hash' => hash('sha256', 'https://example.com/source'), 'consulted_at' => now(), 'verified' => false]);
        }
        $draft = $destination->draft()->create(['news_research_id' => $research->id, 'editorial_profile_version_id' => $version->id, 'title' => 'Contenido único '.fake()->uuid(), 'summary' => 'Resumen', 'body' => 'Cuerpo', 'tags' => [], 'source_references' => $withSource ? ['https://example.com/source'] : [], 'editorial_warnings' => [], 'status' => 'GENERANDO', 'model' => 'gpt-test', 'generated_at' => now()]);

        return [$root, $draft, $media];
    }
}
