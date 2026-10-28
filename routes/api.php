<?php

use App\Http\Controllers\Api\V1\ContentChangeController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NewsRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['api.request-id', 'api.usage', 'media.api', 'throttle:media-api'])->group(function (): void {
    Route::get('me', MeController::class)->name('me');

    Route::middleware('media.scope:content:read')->group(function (): void {
        Route::get('contents', [ContentController::class, 'index'])->name('contents.index');
        Route::get('contents/{publicId}', [ContentController::class, 'show'])->whereUuid('publicId')->name('contents.show');
        Route::get('changes', ContentChangeController::class)->name('changes.index');
    });

    Route::middleware('media.scope:requests:write')->group(function (): void {
        Route::post('requests', [NewsRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{publicId}', [NewsRequestController::class, 'show'])->whereUuid('publicId')->name('requests.show');
    });
});
