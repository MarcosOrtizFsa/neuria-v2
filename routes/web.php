<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/panel', DashboardController::class)->name('panel');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    require __DIR__.'/admin.php';
});
