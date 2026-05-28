<?php

use App\Http\Controllers\Api\PatchNoteController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]);
});

Route::apiResource('patch-notes', PatchNoteController::class);
