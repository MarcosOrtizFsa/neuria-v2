<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditorialProfile;
use App\Models\EditorialSource;
use App\Models\MediaOutlet;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EditorialSourceController extends Controller
{
    public function store(Request $request, MediaOutlet $mediaOutlet, EditorialProfile $profile, AuditService $audit): RedirectResponse
    {
        abort_unless($profile->media_outlet_id === $mediaOutlet->id, 404);
        Gate::authorize('editEditorial', $mediaOutlet);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'url' => ['nullable', 'url', 'max:2048', Rule::unique('editorial_sources')->where('editorial_profile_id', $profile->id)], 'type' => ['required', Rule::in(['PREFERIDA', 'RESTRINGIDA', 'REFERENCIA'])], 'priority' => ['required', 'integer', 'between:1,999'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $source = $profile->sources()->create($data);
        $audit->record($request, 'CREAR_FUENTE', 'LINEA_EDITORIAL', $source);

        return back()->with('success', 'Fuente agregada.');
    }

    public function destroy(Request $request, MediaOutlet $mediaOutlet, EditorialProfile $profile, EditorialSource $source, AuditService $audit): RedirectResponse
    {
        abort_unless($profile->media_outlet_id === $mediaOutlet->id && $source->editorial_profile_id === $profile->id, 404);
        Gate::authorize('editEditorial', $mediaOutlet);
        $audit->record($request, 'ELIMINAR_FUENTE', 'LINEA_EDITORIAL', $source);
        $source->delete();

        return back()->with('success', 'Fuente eliminada.');
    }
}
