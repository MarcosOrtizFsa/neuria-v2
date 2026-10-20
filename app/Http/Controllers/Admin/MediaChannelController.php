<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaChannelRequest;
use App\Http\Requests\UpdateMediaChannelRequest;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MediaChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(MediaOutlet $mediaOutlet): RedirectResponse
    {
        Gate::authorize('view', $mediaOutlet);

        return redirect()->route('admin.medios.show', $mediaOutlet);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MediaOutlet $mediaOutlet): View
    {
        Gate::authorize('manageChannels', $mediaOutlet);

        return view('admin.channels.form', ['mediaOutlet' => $mediaOutlet, 'mediaChannel' => new MediaChannel]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediaChannelRequest $request, MediaOutlet $mediaOutlet, AuditService $audit): RedirectResponse
    {
        $channel = $mediaOutlet->channels()->create($request->validated());
        $audit->record($request, 'CREAR_CANAL', 'MEDIOS', $channel);

        return redirect()->route('admin.medios.show', $mediaOutlet)->with('success', 'Canal creado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaOutlet $mediaOutlet, MediaChannel $mediaChannel): RedirectResponse
    {
        abort_unless($mediaChannel->media_outlet_id === $mediaOutlet->id, 404);
        Gate::authorize('view', $mediaChannel);

        return redirect()->route('admin.medios.show', $mediaOutlet);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MediaOutlet $mediaOutlet, MediaChannel $mediaChannel): View
    {
        abort_unless($mediaChannel->media_outlet_id === $mediaOutlet->id, 404);
        Gate::authorize('update', $mediaChannel);

        return view('admin.channels.form', compact('mediaOutlet', 'mediaChannel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaChannelRequest $request, MediaOutlet $mediaOutlet, MediaChannel $mediaChannel, AuditService $audit): RedirectResponse
    {
        abort_unless($mediaChannel->media_outlet_id === $mediaOutlet->id, 404);
        $mediaChannel->update($request->validated());
        $audit->record($request, 'MODIFICAR_CANAL', 'MEDIOS', $mediaChannel);

        return redirect()->route('admin.medios.show', $mediaOutlet)->with('success', 'Canal actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MediaOutlet $mediaOutlet, MediaChannel $mediaChannel, AuditService $audit): RedirectResponse
    {
        abort_unless($mediaChannel->media_outlet_id === $mediaOutlet->id, 404);
        Gate::authorize('delete', $mediaChannel);
        $mediaChannel->update(['status' => 'INACTIVO']);
        $audit->record($request, 'DESACTIVAR_CANAL', 'MEDIOS', $mediaChannel);

        return back()->with('success', 'Canal desactivado.');
    }
}
