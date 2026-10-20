<?php

namespace App\Services;

use App\Models\MediaApiCredential;
use App\Models\MediaOutlet;
use App\Models\User;
use Illuminate\Support\Str;

class MediaCredentialService
{
    /** @return array{credential: MediaApiCredential, plain_text_token: string} */
    public function create(MediaOutlet $mediaOutlet, User $creator, string $name, array $scopes, ?string $expiresAt = null): array
    {
        $secret = Str::random(64);
        $publicId = 'nria_'.Str::lower(Str::random(16));
        $plainTextToken = $publicId.'.'.$secret;
        $credential = $mediaOutlet->apiCredentials()->create(['created_by' => $creator->id, 'name' => $name, 'public_id' => $publicId, 'token_hash' => hash('sha256', $plainTextToken), 'token_prefix' => substr($plainTextToken, 0, 20), 'scopes' => array_values(array_intersect($scopes, ['content:read', 'content:import'])), 'expires_at' => $expiresAt]);

        return ['credential' => $credential, 'plain_text_token' => $plainTextToken];
    }

    public function revoke(MediaApiCredential $credential): void
    {
        $credential->update(['revoked_at' => now()]);
    }

    public function verify(string $plainTextToken): ?MediaApiCredential
    {
        $credential = MediaApiCredential::where('token_hash', hash('sha256', $plainTextToken))->first();

        return $credential?->isActive() ? $credential : null;
    }
}
