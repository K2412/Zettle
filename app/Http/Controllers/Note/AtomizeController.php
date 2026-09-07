<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpawnRequest;
use App\Models\Note;
use App\Services\Note\Assists\AtomizeAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The two-rail Atomize seam. `run` is the read-only lookup rail: a POST (AI
 * generation is non-idempotent and billable, so it is not a prefetchable GET)
 * that authorizes viewing and returns suggestions as JSON for a background
 * fetch. `spawn` is the write rail: an Inertia visit that authorizes updating
 * (via the Form Request), creates the accepted notes, flashes a toast, and
 * redirects back. Neither rail ever writes the origin note's title or body.
 */
class AtomizeController extends Controller
{
    public function __construct(private AtomizeAssist $atomize) {}

    public function run(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json($this->atomize->run($note));
    }

    public function spawn(SpawnRequest $request, Note $note): RedirectResponse
    {
        $spawned = $this->atomize->spawnPermanent($note, $request->validated()['titles']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count note spawned.|:count notes spawned.', count($spawned)),
        ]);

        return back();
    }
}
