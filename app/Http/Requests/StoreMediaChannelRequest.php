<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageChannels', $this->route('mediaOutlet')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:WEB,FACEBOOK,INSTAGRAM,LINKEDIN,X,OTRO'], 'url' => ['nullable', 'url', 'max:2048'], 'external_identifier' => ['nullable', 'string', 'max:255'], 'status' => ['required', 'in:ACTIVO,INACTIVO'], 'editorial_overrides' => ['nullable', 'array']];
    }
}
