<?php

namespace App\Services;

use App\Models\EditorialProfile;
use App\Models\MediaOutlet;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditorialProfileService
{
    public function save(MediaOutlet $mediaOutlet, User $user, array $data): EditorialProfile
    {
        return DB::transaction(function () use ($mediaOutlet, $user, $data): EditorialProfile {
            $profile = EditorialProfile::whereBelongsTo($mediaOutlet)->lockForUpdate()->first();
            $nextVersion = ($profile?->current_version ?? 0) + 1;
            $attributes = Arr::except($data, ['change_note']);
            $attributes['current_version'] = $nextVersion;
            $profile = EditorialProfile::updateOrCreate(['media_outlet_id' => $mediaOutlet->id], $attributes);
            $profile->versions()->create(['user_id' => $user->id, 'version' => $nextVersion, 'configuration' => $profile->only(['name', 'language', 'identity', 'production', 'sources_config', 'restrictions', 'additional_instructions']), 'change_note' => $data['change_note'] ?? null]);

            return $profile;
        });
    }

    public function restore(EditorialProfile $profile, int $version, User $user): EditorialProfile
    {
        $snapshot = $profile->versions()->where('version', $version)->firstOrFail();

        return $this->save($profile->mediaOutlet, $user, [...$snapshot->configuration, 'change_note' => "Restauración de la versión {$version}"]);
    }
}
