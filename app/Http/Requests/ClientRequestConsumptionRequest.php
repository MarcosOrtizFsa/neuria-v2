<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequestConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->role?->code === 'CLIENTE' || ($user?->hasPermission('API_CONSUMOS.VER') ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(['PENDIENTE', 'INVESTIGANDO', 'INVESTIGACION_COMPLETA', 'GENERANDO', 'COMPLETADA', 'PARCIALMENTE_COMPLETADA', 'ERROR', 'CANCELADA'])],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function selectedMonth(): CarbonImmutable
    {
        $timezone = 'America/Argentina/Buenos_Aires';
        $month = $this->validated('month') ?? now($timezone)->format('Y-m');

        return CarbonImmutable::createFromFormat('!Y-m', $month, $timezone)->startOfMonth();
    }
}
