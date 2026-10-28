<?php

namespace App\Http\Requests\Api;

use App\Models\MediaApiCredential;
use App\Models\MediaChannel;
use App\Rules\SafePublicUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNewsRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('media_api_credential') instanceof MediaApiCredential;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var MediaApiCredential $credential */
        $credential = $this->attributes->get('media_api_credential');

        return [
            'title' => ['required', 'string', 'max:240'],
            'description' => ['required', 'string', 'max:3000'],
            'context' => ['nullable', 'string', 'max:20000'],
            'specific_instructions' => ['nullable', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:160'],
            'priority' => ['required', Rule::in(['BAJA', 'NORMAL', 'ALTA', 'URGENTE'])],
            'event_at' => ['nullable', 'date'],
            'web_search_enabled' => ['required', 'boolean'],
            'initial_urls' => ['nullable', 'array', 'max:10'],
            'initial_urls.*' => ['url', 'max:2048', new SafePublicUrl],
            'initial_urls_text' => ['nullable', 'string', 'max:20480'],
            'research_notes' => ['nullable', 'string', 'max:5000'],
            'channel_ids' => ['required', 'array', 'min:1'],
            'channel_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('media_channels', 'id')->where('media_outlet_id', $credential->media_outlet_id),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $idempotencyKey = $this->idempotencyKey();
            if ($idempotencyKey !== null && ! preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $idempotencyKey)) {
                $validator->errors()->add('idempotency_key', 'La clave de idempotencia no tiene un formato válido.');
            }

            if ($validator->errors()->has('channel_ids')) {
                return;
            }

            $channels = MediaChannel::query()
                ->with('mediaOutlet.editorialProfile')
                ->whereKey($this->input('channel_ids', []))
                ->get();

            foreach ($channels as $channel) {
                if ($channel->status !== 'ACTIVO') {
                    $validator->errors()->add('channel_ids', "El canal {$channel->name} no está activo.");
                }
                if ($channel->mediaOutlet?->editorialProfile === null) {
                    $validator->errors()->add('channel_ids', 'El medio no tiene línea editorial configurada.');
                }
            }
        }];
    }

    public function idempotencyKey(): ?string
    {
        $key = trim((string) $this->header('Idempotency-Key', ''));

        return $key !== '' ? $key : null;
    }

    protected function prepareForValidation(): void
    {
        /** @var MediaApiCredential|null $credential */
        $credential = $this->attributes->get('media_api_credential');
        $channelIds = $this->input('channel_ids');

        if ($credential && ! is_array($channelIds)) {
            $channelIds = MediaChannel::query()
                ->where('media_outlet_id', $credential->media_outlet_id)
                ->where('status', 'ACTIVO')
                ->pluck('id')
                ->all();
        }

        $initialUrls = $this->input('initial_urls', []);
        $this->merge([
            'priority' => $this->input('priority', 'NORMAL'),
            'web_search_enabled' => $this->has('web_search_enabled') ? $this->boolean('web_search_enabled') : true,
            'channel_ids' => $channelIds,
            'initial_urls_text' => is_array($initialUrls) ? implode(PHP_EOL, $initialUrls) : null,
        ]);
    }
}
