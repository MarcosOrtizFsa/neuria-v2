<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ClientDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['month' => ['nullable', 'date_format:Y-m']];
    }

    public function selectedMonth(): CarbonImmutable
    {
        $timezone = 'America/Argentina/Buenos_Aires';
        $month = $this->validated('month') ?? now($timezone)->format('Y-m');

        return CarbonImmutable::createFromFormat('!Y-m', $month, $timezone)->startOfMonth();
    }
}
