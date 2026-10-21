<?php

namespace Database\Factories;

use App\Models\AutomationTask;
use App\Models\AutomationTaskVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationTaskVersion>
 */
class AutomationTaskVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $configuration = ['name' => 'Noticias económicas', 'type' => 'NEWS_RESEARCH_AND_WRITE', 'topic' => 'Economía argentina', 'instructions' => 'Buscar novedades recientes.', 'editorial_approach' => 'Mirada crítica y factual.', 'language' => 'es-AR', 'domains' => ['argentina.gob.ar'], 'frequency' => 'SELECTED_DAYS', 'days' => [1, 2, 3, 4, 5], 'time' => '12:00', 'timezone' => 'America/Argentina/Buenos_Aires', 'min_characters' => 700, 'max_characters' => 1000, 'destination' => 'NEURIA_PANEL', 'no_news_policy' => 'NO_CONTENT'];

        return ['automation_task_id' => AutomationTask::factory(), 'created_by' => User::factory(), 'version' => 1, 'configuration' => $configuration, 'configuration_hash' => hash('sha256', json_encode($configuration))];
    }
}
