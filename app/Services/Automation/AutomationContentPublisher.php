<?php

namespace App\Services\Automation;

use App\Models\AutomationResult;
use App\Models\GeneratedDraft;
use App\Models\MediaChannel;
use App\Models\NewsRequest;
use App\Services\ContentAvailabilityService;
use Illuminate\Support\Str;

class AutomationContentPublisher
{
    public function __construct(private readonly ContentAvailabilityService $availability) {}

    public function publish(AutomationResult $result): GeneratedDraft
    {
        $result = AutomationResult::query()
            ->with(['run.task', 'run.version', 'sources'])
            ->lockForUpdate()
            ->findOrFail($result->id);

        if ($result->generated_draft_id) {
            return GeneratedDraft::findOrFail($result->generated_draft_id);
        }

        $configuration = $result->run->version->configuration;
        $channel = MediaChannel::query()
            ->with('mediaOutlet')
            ->whereKey($configuration['media_channel_id'])
            ->where('media_outlet_id', $configuration['media_outlet_id'])
            ->where('status', 'ACTIVO')
            ->firstOrFail();

        if ($channel->mediaOutlet->organization_id !== $result->run->organization_id) {
            throw new \DomainException('El destino de la automatización no pertenece a su organización.');
        }

        $sourceReferences = $result->sources->map(fn ($source): array => [
            'title' => $source->title,
            'url' => $source->url,
            'publisher' => $source->publisher,
            'citation' => $source->citation,
        ])->values()->all();

        $request = NewsRequest::create([
            'requested_by' => $result->run->task->created_by,
            'title' => $result->title,
            'description' => 'Contenido generado por Piloto Automático.',
            'context' => $configuration['topic'],
            'specific_instructions' => $configuration['instructions'],
            'category' => 'NOTICIA',
            'priority' => 'NORMAL',
            'web_search_enabled' => true,
            'initial_urls' => $result->sources->pluck('url')->all(),
            'research_notes' => 'Ejecución automática '.$result->run->public_id,
            'status' => 'COMPLETADA',
            'progress' => 100,
        ]);
        $request->statusHistory()->create([
            'user_id' => $result->run->task->created_by,
            'to_status' => 'COMPLETADA',
            'reason' => 'Resultado válido producido por Piloto Automático.',
        ]);

        $destination = $request->destinations()->create([
            'media_outlet_id' => $channel->media_outlet_id,
            'media_channel_id' => $channel->id,
            'editorial_profile_version_id' => $configuration['editorial_profile_version_id'],
            'status' => 'COMPLETADA',
        ]);
        $research = $request->researches()->create([
            'version' => 1,
            'status' => 'COMPLETADA',
            'model' => $result->provider_metadata['model'] ?? null,
            'openai_response_id' => $result->provider_metadata['response_id'] ?? null,
            'result' => ['title' => $result->title, 'body' => $result->body],
            'warnings' => $result->warnings,
            'started_at' => $result->run->started_at,
            'completed_at' => $result->run->finished_at ?? now(),
        ]);

        foreach ($result->sources as $source) {
            $research->sources()->create([
                'title' => $source->title,
                'url' => $source->url,
                'url_hash' => hash('sha256', $source->url),
                'publisher' => $source->publisher,
                'published_at' => $source->published_at,
                'consulted_at' => $source->consulted_at,
                'relevant_summary' => $source->citation,
                'verified' => $source->publication_date_verified,
            ]);
        }

        $draft = $destination->draft()->create([
            'news_research_id' => $research->id,
            'editorial_profile_version_id' => $configuration['editorial_profile_version_id'],
            'title' => $result->title,
            'summary' => Str::limit(strip_tags((string) $result->body), 300),
            'body' => $result->body,
            'category' => 'NOTICIA',
            'tags' => [],
            'source_references' => $sourceReferences,
            'editorial_warnings' => $result->warnings ?? [],
            'status' => 'GENERANDO',
            'model' => $result->provider_metadata['model'] ?? null,
        ]);

        if (! $this->availability->validateAndMakeAvailable($draft)) {
            throw new \DomainException('El contenido automático no superó la validación de distribución.');
        }

        $result->update(['generated_draft_id' => $draft->id]);

        return $draft->fresh();
    }
}
