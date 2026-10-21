<?php

use App\Http\Controllers\Api\V1\ContentChangeController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['api.usage', 'media.api', 'media.scope:content:read', 'throttle:media-api'])->group(function (): void {
    Route::get('contents', [ContentController::class, 'index'])->name('contents.index');
    Route::get('contents/{publicId}', [ContentController::class, 'show'])->whereUuid('publicId')->name('contents.show');
    Route::get('changes', ContentChangeController::class)->name('changes.index');
    Route::get('me', MeController::class)->name('me');
});
