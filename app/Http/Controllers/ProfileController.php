<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()->load('organization')]);
    }

    public function update(UpdateProfileRequest $request, AuditService $audit): RedirectResponse
    {
        $request->user()->update($request->validated());
        $audit->record($request, 'MODIFICAR_PERFIL', 'USUARIOS', $request->user());

        return back()->with('success', 'Perfil actualizado.');
    }
}
