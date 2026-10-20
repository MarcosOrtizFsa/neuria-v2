<?php

use App\Http\Controllers\Admin\EditorialProfileController;
use App\Http\Controllers\Admin\EditorialSourceController;
use App\Http\Controllers\Admin\MediaApiCredentialController;
use App\Http\Controllers\Admin\MediaChannelController;
use App\Http\Controllers\Admin\MediaOutletController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('usuarios', UserController::class)->parameters(['usuarios' => 'user'])->middleware('permission:USUARIOS.VER');
    Route::resource('organizaciones', OrganizationController::class)->parameters(['organizaciones' => 'organization'])->middleware('permission:USUARIOS.VER');
    Route::resource('medios', MediaOutletController::class)->parameters(['medios' => 'mediaOutlet']);
    Route::resource('medios.canales', MediaChannelController::class)->parameters(['medios' => 'mediaOutlet', 'canales' => 'mediaChannel']);
    Route::get('medios/{mediaOutlet}/credenciales/create', [MediaApiCredentialController::class, 'create'])->name('medios.credenciales.create');
    Route::post('medios/{mediaOutlet}/credenciales', [MediaApiCredentialController::class, 'store'])->name('medios.credenciales.store');
    Route::delete('medios/{mediaOutlet}/credenciales/{credential}', [MediaApiCredentialController::class, 'destroy'])->name('medios.credenciales.destroy');
    Route::get('medios/{mediaOutlet}/linea-editorial', [EditorialProfileController::class, 'edit'])->name('medios.editorial.edit');
    Route::put('medios/{mediaOutlet}/linea-editorial', [EditorialProfileController::class, 'update'])->name('medios.editorial.update');
    Route::get('medios/{mediaOutlet}/linea-editorial/historial', [EditorialProfileController::class, 'history'])->name('medios.editorial.history');
    Route::post('medios/{mediaOutlet}/linea-editorial/{profile}/restaurar/{version}', [EditorialProfileController::class, 'restore'])->name('medios.editorial.restore');
    Route::post('medios/{mediaOutlet}/linea-editorial/{profile}/fuentes', [EditorialSourceController::class, 'store'])->name('medios.editorial.sources.store');
    Route::delete('medios/{mediaOutlet}/linea-editorial/{profile}/fuentes/{source}', [EditorialSourceController::class, 'destroy'])->name('medios.editorial.sources.destroy');
});
