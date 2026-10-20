<?php

namespace App\Http\Requests;

use App\Models\MediaOutlet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaOutletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', MediaOutlet::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['organization_id' => ['required', 'exists:organizations,id'], 'name' => ['required', 'string', 'max:255'], 'trade_name' => ['nullable', 'string', 'max:255'], 'public_identifier' => ['required', 'alpha_dash', 'max:100', 'unique:media_outlets,public_identifier'], 'main_url' => ['nullable', 'url', 'max:2048'], 'description' => ['nullable', 'string', 'max:5000'], 'contact_email' => ['nullable', 'email', 'max:255'], 'contact_phone' => ['nullable', 'string', 'max:50'], 'type' => ['required', 'in:PORTAL_NOTICIAS,MEDIO_DIGITAL,MEDIO_INSTITUCIONAL,AGENCIA_NOTICIAS,OTRO'], 'status' => ['required', 'in:ACTIVO,INACTIVO,SUSPENDIDO'], 'internal_notes' => ['nullable', 'string', 'max:5000']];
    }
}
