<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load(['role', 'organization']);
        if ($user->role?->code === 'CLIENTE') {
            return view('dashboard.client', compact('user'));
        }
        $metrics = $user->hasPermission('USUARIOS.VER') ? ['users' => User::count(), 'active' => User::where('is_active', true)->count(), 'inactive' => User::where('is_active', false)->count(), 'clients' => Organization::count(), 'roles' => Role::count()] : [];

        return view('dashboard.admin', compact('user', 'metrics'));
    }
}
