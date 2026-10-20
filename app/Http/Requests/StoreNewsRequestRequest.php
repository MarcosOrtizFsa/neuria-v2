<?php

namespace App\Http\Requests;

use App\Models\MediaChannel;
use App\Models\NewsRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNewsRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', NewsRequest::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'context' => ['nullable', 'string', 'max:20000'],
            'specific_instructions' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', Rule::in(['BAJA', 'NORMAL', 'ALTA', 'URGENTE'])],
            'event_at' => ['nullable', 'date'],
            'web_search_enabled' => ['nullable', 'boolean'],
            'initial_urls_text' => ['nullable', 'string', 'max:10000'],
            'research_notes' => ['nullable', 'string', 'max:5000'],
            'channel_ids' => ['required', 'array', 'min:1'],
            'channel_ids.*' => ['integer', 'distinct', 'exists:media_channels,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('channel_ids')) {
                return;
            }

            $channels = MediaChannel::query()->with('mediaOutlet.editorialProfile')->whereKey($this->input('channel_ids', []))->get();
            foreach ($channels as $channel) {
                if ($channel->status !== 'ACTIVO' || $channel->mediaOutlet?->status !== 'ACTIVO') {
                    $validator->errors()->add('channel_ids', "El destino {$channel->name} no está activo.");
                }
                if ($channel->mediaOutlet?->editorialProfile === null) {
                    $validator->errors()->add('channel_ids', "El medio {$channel->mediaOutlet?->name} no tiene línea editorial.");
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['web_search_enabled' => $this->boolean('web_search_enabled')]);
    }
}
