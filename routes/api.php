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

Route::apiResource('patch-notes', PatchNoteController::class)->except('show');
