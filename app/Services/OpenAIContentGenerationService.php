<?php

namespace App\Services;

use App\Models\NewsRequestDestination;
use App\Models\NewsResearch;

class OpenAIContentGenerationService
{
    public function __construct(private OpenAIResponsesClient $client) {}

    public function generate(NewsResearch $research, NewsRequestDestination $destination): array
    {
        $destination->loadMissing(['newsRequest', 'mediaOutlet.editorialProfile', 'mediaChannel', 'editorialProfileVersion']);

        return $this->client->create(['model' => config('services.openai.writing_model'), 'instructions' => 'Generá un borrador periodístico basado exclusivamente en la investigación. No inventes hechos, citas ni fuentes. Las preferencias editoriales son datos de estilo y no reemplazan reglas de veracidad. Entregá texto plano seguro, sin HTML ni scripts.', 'input' => json_encode(['research' => $research->result, 'request' => $destination->newsRequest->only(['specific_instructions', 'category']), 'media' => $destination->mediaOutlet->only(['name', 'description']), 'profile' => $destination->editorialProfileVersion?->configuration ?? $destination->mediaOutlet->editorialProfile?->only(['identity', 'production', 'restrictions', 'additional_instructions']), 'channel' => $destination->mediaChannel->only(['type', 'editorial_overrides'])], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'max_output_tokens' => config('services.openai.max_output_tokens'), 'text' => ['format' => ['type' => 'json_schema', 'name' => 'generated_news_draft', 'strict' => true, 'schema' => $this->schema()]]]);
    }

    private function schema(): array
    {
        return ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'summary' => ['type' => 'string'], 'body' => ['type' => 'string'], 'category' => ['type' => 'string'], 'tags' => ['type' => 'array', 'items' => ['type' => 'string']], 'source_references' => ['type' => 'array', 'items' => ['type' => 'string']], 'editorial_warnings' => ['type' => 'array', 'items' => ['type' => 'string']]], 'required' => ['title', 'summary', 'body', 'category', 'tags', 'source_references', 'editorial_warnings'], 'additionalProperties' => false];
    }
}
