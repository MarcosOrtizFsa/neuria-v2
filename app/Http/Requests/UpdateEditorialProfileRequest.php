<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEditorialProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editEditorial', $this->route('mediaOutlet')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'language' => ['required', 'string', 'max:10'], 'identity' => ['required', 'array'], 'identity.audience' => ['nullable', 'string', 'max:2000'], 'identity.focus' => ['nullable', 'string', 'max:5000'], 'identity.tone' => ['nullable', 'string', 'max:1000'], 'identity.style' => ['nullable', 'string', 'max:2000'], 'identity.preferred_vocabulary' => ['nullable', 'string', 'max:5000'], 'identity.avoid_expressions' => ['nullable', 'string', 'max:5000'], 'production' => ['required', 'array'], 'production.preferred_length' => ['nullable', 'string', 'max:255'], 'production.title_structure' => ['nullable', 'string', 'max:2000'], 'production.summary_style' => ['nullable', 'string', 'max:2000'], 'production.body_structure' => ['nullable', 'string', 'max:5000'], 'production.use_subtitles' => ['nullable', 'boolean'], 'production.quote_preferences' => ['nullable', 'string', 'max:2000'], 'production.output_format' => ['nullable', 'string', 'max:255'], 'sources_config' => ['required', 'array'], 'restrictions' => ['required', 'array'], 'additional_instructions' => ['nullable', 'string', 'max:10000'], 'change_note' => ['nullable', 'string', 'max:1000']];
    }
}
