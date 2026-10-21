<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class OpenAIResponsesClient
{
    public function create(array $payload): array
    {
        $response = $this->client()->post('https://api.openai.com/v1/responses', $payload)->throw()->json();
        $content = collect($response['output'] ?? [])->flatMap(fn (array $item): array => $item['content'] ?? []);
        $message = $content->first(fn (array $item): bool => ($item['type'] ?? null) === 'output_text');
        $text = is_array($message) ? ($message['text'] ?? null) : null;

        if (! is_string($text)) {
            $refusal = $content->first(fn (array $item): bool => ($item['type'] ?? null) === 'refusal');
            throw new RuntimeException(is_array($refusal) ? 'OpenAI rechazó la solicitud: '.($refusal['refusal'] ?? 'sin detalle') : 'OpenAI no devolvió contenido estructurado utilizable.');
        }

        try {
            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('OpenAI devolvió JSON inválido.', previous: $exception);
        }

        return ['id' => $response['id'] ?? null, 'model' => $response['model'] ?? $payload['model'], 'data' => $data, 'usage' => $response['usage'] ?? [], 'output' => $response['output'] ?? []];
    }

    private function client(): PendingRequest
    {
        $key = config('services.openai.key');
        if (! $key) {
            throw new RuntimeException('OPENAI_API_KEY no está configurada.');
        }

        return Http::withToken($key)
            ->acceptJson()
            ->timeout(config('services.openai.timeout'))
            ->retry(config('services.openai.max_retries'), 1000, fn (\Throwable $exception): bool => $exception instanceof RequestException && in_array($exception->response->status(), [408, 409, 429, 500, 502, 503, 504], true));
    }
}
