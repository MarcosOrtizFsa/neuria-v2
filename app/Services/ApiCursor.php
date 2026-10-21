<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class ApiCursor
{
    public function encode(array $payload): string
    {
        return Crypt::encryptString(json_encode(['v' => 1] + $payload, JSON_THROW_ON_ERROR));
    }

    public function decode(?string $cursor, string $type, int $mediaOutletId): ?array
    {
        if (! $cursor) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages(['cursor' => 'El cursor no es válido.']);
        }

        if (($payload['v'] ?? null) !== 1 || ($payload['type'] ?? null) !== $type || ($payload['media'] ?? null) !== $mediaOutletId) {
            throw ValidationException::withMessages(['cursor' => 'El cursor no corresponde a esta consulta.']);
        }

        return $payload;
    }
}
