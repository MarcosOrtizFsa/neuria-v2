<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrganizationPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization !== null && ($this->user()?->can('update', $organization) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', Rule::in(['ARS', 'USD'])],
            'monthly_limit' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/'],
            'active_task_limit' => ['required', 'integer', 'min:0', 'max:10000'],
            'daily_run_limit' => ['required', 'integer', 'min:0', 'max:10000'],
            'concurrency_limit' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function monthlyLimitMinor(): int
    {
        [$whole, $decimal] = array_pad(explode('.', (string) $this->validated('monthly_limit'), 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['currency', 'monthly_limit'])) {
                return;
            }

            $budget = $this->route('organization')?->automationBudget;
            if ($budget === null || ! $budget->period_start->isSameMonth(now())) {
                return;
            }

            $committedMinor = (int) $budget->reserved_minor + (int) $budget->settled_minor;
            if ($this->monthlyLimitMinor() < $committedMinor) {
                $validator->errors()->add('monthly_limit', 'El importe no puede ser menor que lo liquidado y reservado en el período actual.');
            }

            if ($budget->currency !== $this->validated('currency') && $committedMinor > 0) {
                $validator->errors()->add('currency', 'No se puede cambiar la moneda mientras existan importes liquidados o reservados.');
            }
        }];
    }
}
