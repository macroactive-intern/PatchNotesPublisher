<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatchNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PatchNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', PatchNote::class);

        return response()->json(
            PatchNote::with('user')->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', PatchNote::class);

        $patchNote = PatchNote::create($this->validatedData($request));

        return response()->json($patchNote->load('user'), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(PatchNote $patchNote): JsonResponse
    {
        Gate::authorize('view', $patchNote);

        return response()->json($patchNote->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PatchNote $patchNote): JsonResponse
    {
        Gate::authorize('update', $patchNote);

        $patchNote->update($this->validatedData($request, updating: true));

        return response()->json($patchNote->load('user'));
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

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $updating = false): array
    {
        $presence = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$presence, 'string', 'max:255'],
            'content' => [$presence, 'string'],
            'published' => ['sometimes', 'boolean'],
            'user_id' => [$presence, 'integer', Rule::exists('users', 'id')],
        ]);
    }
}
