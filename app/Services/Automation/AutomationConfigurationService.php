<?php

namespace App\Services\Automation;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AutomationConfigurationService
{
    /** @return array<string, mixed> */
    public function fromValidated(array $data): array
    {
        $domains = collect($data['domains'] ?? [])->map(function (string $domain): string {
            $host = parse_url(str_contains($domain, '://') ? $domain : 'https://'.$domain, PHP_URL_HOST);

            return mb_strtolower(trim((string) $host, '.'));
        })->filter()->unique()->values()->all();

        return [
            'name' => trim($data['name']),
            'type' => 'NEWS_RESEARCH_AND_WRITE',
            'media_channel_id' => (int) $data['media_channel_id'],
            'topic' => trim($data['topic']),
            'instructions' => trim((string) ($data['instructions'] ?? '')),
            'editorial_approach' => trim((string) ($data['editorial_approach'] ?? '')),
            'language' => $data['language'] ?? 'es-AR',
            'domains' => $domains,
            'frequency' => $data['frequency'],
            'days' => array_values(array_unique(array_map('intval', $data['days'] ?? []))),
            'time' => $data['time'],
            'timezone' => $data['timezone'] ?? config('services.automation.default_timezone'),
            'min_characters' => (int) $data['min_characters'],
            'max_characters' => (int) $data['max_characters'],
            'destination' => 'NEURIA_PANEL',
            'no_news_policy' => $data['no_news_policy'] ?? 'NO_CONTENT',
        ];
    }

    /** @return array<string, mixed> */
    public function interpretationToForm(array $interpretation): array
    {
        return Arr::only($interpretation, ['name', 'topic', 'instructions', 'editorial_approach', 'language', 'domains', 'frequency', 'days', 'time', 'timezone', 'min_characters', 'max_characters', 'no_news_policy']);
    }

    public function hash(array $configuration): string
    {
        return hash('sha256', json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    public function validateInterpretation(array $interpretation): array
    {
        $validated = Validator::make($interpretation, [
            'capability_supported' => ['required', 'boolean'],
            'missing_fields' => ['required', 'array'],
            'missing_fields.*' => ['string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'topic' => ['required', 'string', 'max:1000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'editorial_approach' => ['nullable', 'string', 'max:3000'],
            'language' => ['required', 'string', 'max:20'],
            'domains' => ['present', 'array', 'max:20'],
            'domains.*' => ['required', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i'],
            'frequency' => ['required', Rule::in(['DAILY', 'SELECTED_DAYS', 'WEEKLY'])],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone:all'],
            'min_characters' => ['required', 'integer', 'min:200', 'max:20000'],
            'max_characters' => ['required', 'integer', 'gte:min_characters', 'max:30000'],
            'no_news_policy' => ['required', Rule::in(['NO_CONTENT'])],
        ])->validate();

        if (! $validated['capability_supported']) {
            throw ValidationException::withMessages(['request_text' => 'El pedido excede la capacidad actual: solo se admite investigar noticias y redactar una nota para el panel.']);
        }

        return $validated;
    }
}
