<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatchNoteRequest;
use App\Http\Requests\UpdatePatchNoteRequest;
use App\Models\PatchNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PatchNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PatchNote::class);

        $user = $request->user();

        $notes = PatchNote::with('user')
            ->when(! $user?->isAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('published', true);
                    if ($user?->isEditor()) {
                        $q->orWhere('user_id', $user->id);
                    }
                });
            })
            ->get();

        return response()->json(['data' => $notes]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatchNoteRequest $request): JsonResponse
    {
        Gate::authorize('create', PatchNote::class);

        $patchNote = $request->user()->patchNotes()->create($request->validated());

        return response()->json([
            'data' => $patchNote->refresh()->load('user'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(PatchNote $patchNote): JsonResponse
    {
        Gate::authorize('view', $patchNote);

        return response()->json([
            'data' => $patchNote->load('user'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatchNoteRequest $request, PatchNote $patchNote): JsonResponse
    {
        Gate::authorize('update', $patchNote);

        $patchNote->update($request->validated());

        return response()->json([
            'data' => $patchNote->refresh()->load('user'),
        ]);
    }

    public function publish(PatchNote $patchNote): JsonResponse
    {
        Gate::authorize('publish');

        $patchNote->update([
            'published' => ! $patchNote->published,
        ]);

        return response()->json([
            'data' => $patchNote->refresh()->load('user'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatchNote $patchNote): Response
    {
        Gate::authorize('delete', $patchNote);

        $patchNote->delete();

        return response()->noContent();
    }

}
