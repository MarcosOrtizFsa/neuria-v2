<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEditorialProfileRequest;
use App\Models\EditorialProfile;
use App\Models\MediaOutlet;
use App\Services\AuditService;
use App\Services\EditorialProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EditorialProfileController extends Controller
{
    public function edit(MediaOutlet $mediaOutlet): View
    {
        Gate::authorize('editEditorial', $mediaOutlet);
        $profile = $mediaOutlet->editorialProfile()->with('sources')->firstOrNew(['name' => $mediaOutlet->name, 'language' => 'es-AR', 'identity' => [], 'production' => [], 'sources_config' => [], 'restrictions' => []]);

        return view('admin.editorial.edit', compact('mediaOutlet', 'profile'));
    }

    public function update(UpdateEditorialProfileRequest $request, MediaOutlet $mediaOutlet, EditorialProfileService $service, AuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        if ($request->user()->role?->code === 'CLIENTE') {
            $current = $mediaOutlet->editorialProfile;
            $data = $this->clientData($current, $data);
        }
        $profile = $service->save($mediaOutlet, $request->user(), $data);
        $audit->record($request, 'MODIFICAR_PERFIL_EDITORIAL', 'LINEA_EDITORIAL', $profile, ['version' => $profile->current_version]);

        return redirect()->route('admin.medios.show', $mediaOutlet)->with('success', 'Perfil editorial guardado.');
    }

    public function history(MediaOutlet $mediaOutlet): View
    {
        Gate::authorize('view', $mediaOutlet);
        $profile = $mediaOutlet->editorialProfile?->load('versions.user');

        return view('admin.editorial.history', compact('mediaOutlet', 'profile'));
    }

    public function restore(Request $request, MediaOutlet $mediaOutlet, EditorialProfile $profile, int $version, EditorialProfileService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($profile->media_outlet_id === $mediaOutlet->id, 404);
        abort_unless($request->user()->hasPermission('LINEA_EDITORIAL.RESTAURAR_VERSION'), 403);
        $restored = $service->restore($profile, $version, $request->user());
        $audit->record($request, 'RESTAURAR_PERFIL_EDITORIAL', 'LINEA_EDITORIAL', $restored, ['source_version' => $version, 'new_version' => $restored->current_version]);

        return back()->with('success', 'Versión restaurada como una nueva versión.');
    }

    private function clientData(?EditorialProfile $current, array $submitted): array
    {
        $base = $current?->only(['name', 'language', 'identity', 'production', 'sources_config', 'restrictions', 'additional_instructions']) ?? ['name' => $submitted['name'], 'language' => $submitted['language'], 'identity' => [], 'production' => [], 'sources_config' => [], 'restrictions' => [], 'additional_instructions' => null];
        foreach (['audience', 'tone', 'style', 'preferred_vocabulary'] as $key) {
            $base['identity'][$key] = $submitted['identity'][$key] ?? null;
        } foreach (['preferred_length', 'output_format'] as $key) {
            $base['production'][$key] = $submitted['production'][$key] ?? null;
        } $base['sources_config']['preferred'] = $submitted['sources_config']['preferred'] ?? null;
        $base['additional_instructions'] = $submitted['additional_instructions'] ?? null;
        $base['change_note'] = $submitted['change_note'] ?? null;

        return $base;
    }
}
