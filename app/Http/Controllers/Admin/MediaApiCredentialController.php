<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaApiCredential;
use App\Models\MediaOutlet;
use App\Services\AuditService;
use App\Services\MediaCredentialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MediaApiCredentialController extends Controller
{
    public function create(MediaOutlet $mediaOutlet): View
    {
        Gate::authorize('manageCredentials', $mediaOutlet);

        return view('admin.credentials.create', compact('mediaOutlet'));
    }

    public function store(Request $request, MediaOutlet $mediaOutlet, MediaCredentialService $service, AuditService $audit): Response
    {
        Gate::authorize('manageCredentials', $mediaOutlet);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'scopes' => ['required', 'array', 'min:1'], 'scopes.*' => [Rule::in(['content:read', 'content:import', 'requests:write'])], 'expires_at' => ['nullable', 'date', 'after:now']]);
        $result = $service->create($mediaOutlet, $request->user(), $data['name'], $data['scopes'], $data['expires_at'] ?? null);
        $audit->record($request, 'GENERAR_CREDENCIAL', 'MEDIOS', $result['credential'], ['public_id' => $result['credential']->public_id]);

        return response()->view('admin.credentials.created', ['mediaOutlet' => $mediaOutlet, 'credential' => $result['credential'], 'plainTextToken' => $result['plain_text_token']])->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')->header('Pragma', 'no-cache');
    }

    public function destroy(Request $request, MediaOutlet $mediaOutlet, MediaApiCredential $credential, MediaCredentialService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($credential->media_outlet_id === $mediaOutlet->id, 404);
        Gate::authorize('manageCredentials', $mediaOutlet);
        $service->revoke($credential);
        $audit->record($request, 'REVOCAR_CREDENCIAL', 'MEDIOS', $credential, ['public_id' => $credential->public_id]);

        return back()->with('success', 'Credencial revocada.');
    }
}
