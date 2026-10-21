<?php

namespace App\Http\Requests\Api;

use App\Models\MediaApiCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('media_api_credential') instanceof MediaApiCredential;
    }

    public function rules(): array
    {
        /** @var MediaApiCredential $credential */
        $credential = $this->attributes->get('media_api_credential');

        return [
            'channel_id' => ['nullable', 'integer', Rule::exists('media_channels', 'id')->where('media_outlet_id', $credential->media_outlet_id)],
            'available_from' => ['nullable', 'date'],
            'id' => ['nullable', 'uuid'],
            'cursor' => ['nullable', 'string', 'max:4096'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.config('services.media_api.maximum_page_size', 100)],
        ];
    }
}
