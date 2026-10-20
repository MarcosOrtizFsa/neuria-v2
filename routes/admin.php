<?php

use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('usuarios', UserController::class)->parameters(['usuarios' => 'user'])->middleware('permission:USUARIOS.VER');
    Route::resource('organizaciones', OrganizationController::class)->parameters(['organizaciones' => 'organization'])->middleware('permission:USUARIOS.VER');
});
