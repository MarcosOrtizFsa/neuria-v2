<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDraft;
use App\Services\AuditService;
use App\Services\ContentAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContentAvailabilityController extends Controller
{
    public function suspend(Request $request, GeneratedDraft $content, ContentAvailabilityService $availability, AuditService $audit): RedirectResponse
    {
        Gate::authorize('suspend', $content);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $availability->suspend($content, $request->user(), $data['reason'] ?? null);
        $audit->record($request, 'SUSPENDER_CONTENIDO', 'CONTENIDOS', $content);

        return back()->with('success', 'El contenido fue suspendido y dejó de estar disponible.');
    }

    public function reactivate(Request $request, GeneratedDraft $content, ContentAvailabilityService $availability, AuditService $audit): RedirectResponse
    {
        Gate::authorize('reactivate', $content);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $availability->reactivate($content, $request->user(), $data['reason'] ?? null);
        $audit->record($request, 'REACTIVAR_CONTENIDO', 'CONTENIDOS', $content);

        return back()->with('success', 'El contenido volvió a estar disponible.');
    }

    public function retire(Request $request, GeneratedDraft $content, ContentAvailabilityService $availability, AuditService $audit): RedirectResponse
    {
        Gate::authorize('retire', $content);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $availability->retire($content, $request->user(), $data['reason'] ?? null);
        $audit->record($request, 'RETIRAR_CONTENIDO', 'CONTENIDOS', $content);

        return back()->with('success', 'El contenido fue retirado de manera permanente.');
    }
}
