<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunAutomationNowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user()?->role?->code === 'CLIENTE' && $this->user()->organization_id !== $this->route('automationTask')?->organization_id) {
            abort(404);
        }

        return $this->user()?->can('runNow', $this->route('automationTask')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'accept_charge' => ['accepted']];
    }
}
