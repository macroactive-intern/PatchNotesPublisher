<?php

namespace App\Http\Middleware;

use App\Models\PatchNote;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class RequirePublishedStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $patchNote = $request->route('patch_note');

        if ($patchNote instanceof PatchNote && Gate::denies('view', $patchNote)) {
            abort($request->user() ? Response::HTTP_FORBIDDEN : Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
