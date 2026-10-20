<?php

namespace Database\Seeders;

use App\Models\EditorialProfile;
use App\Models\EditorialSource;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('DEMO_CLIENT_EMAIL');
        $password = env('DEMO_CLIENT_PASSWORD');

        if (! $email || ! $password) {
            throw new RuntimeException('Configure DEMO_CLIENT_EMAIL y DEMO_CLIENT_PASSWORD en .env.');
        }

        DB::transaction(function () use ($email, $password): void {
            $clientRole = Role::where('code', 'CLIENTE')->firstOrFail();
            $organization = Organization::updateOrCreate(
                ['contact_email' => 'contacto@innovacioneducativahoy.test'],
                ['name' => 'Innovación Educativa Hoy', 'trade_name' => 'IEH', 'phone' => '+54 370 400-0000', 'is_active' => true, 'internal_notes' => 'Cliente de demostración para probar el circuito editorial de NEURIA.'],
            );
            $user = User::updateOrCreate(
                ['email' => $email],
                ['role_id' => $clientRole->id, 'organization_id' => $organization->id, 'name' => 'Cliente', 'last_name' => 'Demostración', 'password' => Hash::make($password), 'is_active' => true],
            );
            $mediaOutlet = MediaOutlet::updateOrCreate(
                ['public_identifier' => 'innovacion-educativa-hoy'],
                ['organization_id' => $organization->id, 'name' => 'Innovación Educativa Hoy', 'trade_name' => 'IEH Noticias', 'main_url' => 'https://innovacioneducativahoy.test', 'description' => 'Portal argentino dedicado a educación, tecnología e inteligencia artificial.', 'contact_email' => 'redaccion@innovacioneducativahoy.test', 'contact_phone' => '+54 370 400-0001', 'type' => 'PORTAL_NOTICIAS', 'status' => 'ACTIVO', 'internal_notes' => 'Medio de demostración; no publicar ni distribuir contenido externamente.'],
            );
            MediaChannel::updateOrCreate(
                ['media_outlet_id' => $mediaOutlet->id, 'type' => 'WEB'],
                ['name' => 'Portal web principal', 'url' => 'https://innovacioneducativahoy.test', 'status' => 'ACTIVO', 'editorial_overrides' => ['preferred_length' => 'Entre 700 y 1.000 palabras', 'tone' => 'Didáctico, claro y profesional', 'output_format' => 'Noticia web con título, bajada, cuerpo, subtítulos y fuentes']],
            );
            $configuration = [
                'name' => 'Perfil editorial educativo y tecnológico',
                'language' => 'es-AR',
                'identity' => ['audience' => 'Docentes, directivos, estudiantes y familias interesadas en innovación educativa.', 'focus' => 'Explicar el impacto práctico de la tecnología y la IA en la educación argentina.', 'tone' => 'Claro, constructivo, didáctico y fundamentado.', 'style' => 'Periodismo explicativo con lenguaje accesible y contexto.', 'preferred_vocabulary' => 'Educación, aprendizaje, innovación, tecnología responsable, inteligencia artificial.', 'avoid_expressions' => 'Sensacionalismo, afirmaciones absolutas y tecnicismos sin explicación.'],
                'production' => ['preferred_length' => 'Entre 700 y 1.000 palabras', 'title_structure' => 'Título informativo y directo.', 'summary_style' => 'Bajada breve que explique relevancia y alcance.', 'body_structure' => 'Introducción, contexto, hechos confirmados, análisis e implicancias.', 'use_subtitles' => true, 'quote_preferences' => 'Solo citas verificadas y atribuidas.', 'output_format' => 'Noticia web en texto estructurado.'],
                'sources_config' => ['preferred' => 'Organismos educativos oficiales, universidades, investigaciones académicas y documentación técnica.', 'restricted' => 'Evitar blogs anónimos y publicaciones sin autor o evidencia verificable.'],
                'restrictions' => ['preferred_topics' => 'IA educativa, ciudadanía digital, formación docente y políticas de innovación.', 'excluded_topics' => 'Promoción comercial encubierta.', 'undesired_approaches' => 'Alarmismo tecnológico o promesas sin evidencia.', 'writing_rules' => 'Distinguir hechos, opiniones e inferencias. Informar límites y riesgos.'],
                'additional_instructions' => 'Relacionar el tema con aplicaciones educativas concretas cuando la evidencia lo permita.',
            ];
            $profile = EditorialProfile::updateOrCreate(['media_outlet_id' => $mediaOutlet->id], [...$configuration, 'current_version' => 1]);
            $profile->versions()->firstOrCreate(['version' => 1], ['user_id' => $user->id, 'configuration' => $configuration, 'change_note' => 'Configuración inicial del cliente de demostración.']);
            $sources = [
                ['name' => 'Ministerio de Educación de la Nación', 'url' => 'https://www.argentina.gob.ar/educacion', 'type' => 'PREFERIDA', 'priority' => 10, 'notes' => 'Normativa, programas y comunicaciones educativas oficiales.'],
                ['name' => 'UNESCO — Educación', 'url' => 'https://www.unesco.org/es/education', 'type' => 'PREFERIDA', 'priority' => 20, 'notes' => 'Informes internacionales y marcos de referencia educativos.'],
                ['name' => 'OEI — Educación, Ciencia y Cultura', 'url' => 'https://oei.int/', 'type' => 'REFERENCIA', 'priority' => 30, 'notes' => 'Estudios y programas iberoamericanos.'],
                ['name' => 'Blogs anónimos sin referencias', 'url' => null, 'type' => 'RESTRINGIDA', 'priority' => 999, 'notes' => 'No utilizar como respaldo factual si no presentan autoría y evidencia verificable.'],
            ];
            foreach ($sources as $source) {
                EditorialSource::updateOrCreate(
                    ['editorial_profile_id' => $profile->id, 'name' => $source['name']],
                    $source,
                );
            }
        });
    }
}
