<?php

namespace App\Rules;

use App\Services\PublicUrlSafetyService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SafePublicUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(PublicUrlSafetyService::class)->isSafe($value)) {
            $fail('La URL indicada no es pública o no es segura.');
        }
    }
}
