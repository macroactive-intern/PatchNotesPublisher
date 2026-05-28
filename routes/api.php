<?php

use App\Http\Controllers\Api\PatchNoteController;
use App\Http\Middleware\RequirePublishedStatus;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]);
});

Route::get('patch-notes/{patch_note}', [PatchNoteController::class, 'show'])
    ->middleware(RequirePublishedStatus::class)
    ->name('patch-notes.show');

Route::get('patch-notes', [PatchNoteController::class, 'index'])
    ->name('patch-notes.index');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('patch-notes', [PatchNoteController::class, 'store'])
        ->name('patch-notes.store');
    Route::patch('patch-notes/{patch_note}/publish', [PatchNoteController::class, 'publish'])
        ->name('patch-notes.publish');
    Route::match(['put', 'patch'], 'patch-notes/{patch_note}', [PatchNoteController::class, 'update'])
        ->name('patch-notes.update');
    Route::delete('patch-notes/{patch_note}', [PatchNoteController::class, 'destroy'])
        ->name('patch-notes.destroy');
});
