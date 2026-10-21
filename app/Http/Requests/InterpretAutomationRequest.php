<?php

namespace App\Http\Requests;

use App\Models\AutomationTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterpretAutomationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AutomationTask::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['request_text' => ['required', 'string', 'min:20', 'max:10000'], 'organization_id' => [Rule::requiredIf($this->user()?->role?->code !== 'CLIENTE'), 'nullable', 'integer', Rule::exists('organizations', 'id')->where('is_active', true)]];
    }
}
