<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAutomationTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()?->role?->code === 'CLIENTE' && $this->user()->organization_id !== $this->route('automationTask')?->organization_id) {
            abort(404);
        }

        return $this->user()?->can('update', $this->route('automationTask')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'], 'topic' => ['required', 'string', 'max:1000'],
            'instructions' => ['nullable', 'string', 'max:5000'], 'editorial_approach' => ['nullable', 'string', 'max:3000'],
            'language' => ['required', 'string', 'max:20'], 'domains_text' => ['required', 'string', 'max:5000'],
            'domains' => ['required', 'array', 'min:1', 'max:20'], 'domains.*' => ['required', 'string', 'max:253', 'regex:/^(?:https?:\/\/)?(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\/?$/i'],
            'frequency' => ['required', Rule::in(['DAILY', 'SELECTED_DAYS', 'WEEKLY'])], 'days' => ['required_unless:frequency,DAILY', 'array', 'min:1'], 'days.*' => ['integer', 'between:1,7'],
            'time' => ['required', 'date_format:H:i'], 'timezone' => ['required', 'timezone:all'],
            'min_characters' => ['required', 'integer', 'min:200', 'max:20000'], 'max_characters' => ['required', 'integer', 'gte:min_characters', 'max:30000'],
            'no_news_policy' => ['required', Rule::in(['NO_CONTENT'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['domains' => collect(preg_split('/[\r\n,]+/', (string) $this->input('domains_text')))->map(fn (string $domain) => trim($domain))->filter()->values()->all()]);
    }
}
