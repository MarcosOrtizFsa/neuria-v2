<?php

namespace Tests\Feature;

use App\Models\EditorialProfile;
use App\Models\MediaOutlet;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class EditorialProfileReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_downloads_native_docx_with_complete_editorial_configuration(): void
    {
        $root = User::factory()->for(Role::factory()->create(['code' => 'ROOT']))->create();
        $mediaOutlet = MediaOutlet::factory()->create(['name' => 'ISFD Educación']);
        $profile = $this->profile($mediaOutlet);
        $profile->sources()->create([
            'name' => 'Ministerio de Educación',
            'url' => 'https://www.argentina.gob.ar/educacion',
            'type' => 'PREFERIDA',
            'priority' => 10,
            'notes' => 'Fuente institucional prioritaria.',
        ]);

        $response = $this->actingAs($root)->get(route('admin.medios.editorial.report', $mediaOutlet));

        $response->assertOk()
            ->assertDownload('linea-editorial-isfd-educacion-v3.docx');
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $response->headers->get('Content-Type'),
        );

        $path = $response->baseResponse->getFile()->getPathname();
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);
        $document = $archive->getFromName('word/document.xml');
        $this->assertIsString($document);
        $this->assertStringContainsString('Informe de línea editorial', $document);
        $this->assertStringContainsString('Docentes y estudiantes', $document);
        $this->assertStringContainsString('Entre 700 y 1.000 palabras', $document);
        $this->assertStringContainsString('Ministerio de Educación', $document);
        $this->assertStringContainsString('Prompt editorial consolidado', $document);
        $this->assertStringContainsString('&lt;script&gt;no ejecutar&lt;/script&gt;', $document);
        $this->assertStringNotContainsString('<script>no ejecutar</script>', $document);
        $archive->close();
        @unlink($path);
    }

    public function test_client_can_download_own_media_report_but_not_another_organizations_report(): void
    {
        $clientOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $client = User::factory()
            ->for(Role::factory()->create(['code' => 'CLIENTE']))
            ->for($clientOrganization)
            ->create();
        $ownMedia = MediaOutlet::factory()->for($clientOrganization)->create();
        $otherMedia = MediaOutlet::factory()->for($otherOrganization)->create();
        $this->profile($ownMedia);
        $this->profile($otherMedia);

        $ownResponse = $this->actingAs($client)->get(route('admin.medios.editorial.report', $ownMedia));

        $ownResponse->assertOk();
        @unlink($ownResponse->baseResponse->getFile()->getPathname());
        $this->actingAs($client)
            ->get(route('admin.medios.editorial.report', $otherMedia))
            ->assertForbidden();
    }

    public function test_report_returns_not_found_when_media_has_no_saved_editorial_profile(): void
    {
        $root = User::factory()->for(Role::factory()->create(['code' => 'ROOT']))->create();
        $mediaOutlet = MediaOutlet::factory()->create();

        $this->actingAs($root)
            ->get(route('admin.medios.editorial.report', $mediaOutlet))
            ->assertNotFound();
    }

    private function profile(MediaOutlet $mediaOutlet): EditorialProfile
    {
        return EditorialProfile::create([
            'media_outlet_id' => $mediaOutlet->id,
            'name' => 'Perfil editorial ISFD',
            'language' => 'es-AR',
            'identity' => [
                'audience' => 'Docentes y estudiantes',
                'tone' => 'Didáctico, claro y profesional',
                'style' => 'Periodismo educativo basado en fuentes verificables',
            ],
            'production' => [
                'preferred_length' => 'Entre 700 y 1.000 palabras',
                'output_format' => 'Texto plano sin etiquetas HTML',
                'use_subtitles' => true,
            ],
            'sources_config' => [
                'preferred' => "UNESCO\nargentina.gob.ar",
                'restricted' => 'Sitios sin autor identificado',
            ],
            'restrictions' => [
                'preferred_topics' => 'Educación, tecnología e inteligencia artificial',
                'writing_rules' => 'Verificar fechas y explicar siglas',
            ],
            'additional_instructions' => '<script>no ejecutar</script>',
            'current_version' => 3,
        ]);
    }
}
