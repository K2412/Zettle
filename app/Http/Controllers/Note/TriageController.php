<?php

namespace App\Http\Controllers\Note;

use App\Enums\NoteType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyTypeRequest;
use App\Models\Note;
use App\Services\Note\Assists\TriageAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The two-rail Triage seam. `run` is the read-only lookup rail: a POST (AI
 * generation is non-idempotent and billable, so it is not a prefetchable GET)
 * that authorizes viewing and returns the triage suggestion as JSON for a
 * background fetch. `applyType` is the write rail: an Inertia visit that
 * authorizes updating (via the Form Request), sets ONLY the note's type,
 * flashes a toast, and redirects back. Neither rail ever writes the note's
 * title or body (ADR-0005).
 */
class TriageController extends Controller
{
    public function __construct(private TriageAssist $triage) {}

    public function run(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $triage = $this->triage->run($note);

        return response()->json([
            'destination' => $triage['destination']->value,
            'note_type' => $triage['note_type']->value,
            'reasoning' => $triage['reasoning'],
        ]);
    }

    public function applyType(ApplyTypeRequest $request, Note $note): RedirectResponse
    {
        $this->triage->applyType($note, NoteType::from($request->validated()['note_type']));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Note type updated.'),
        ]);

        return back();
    }
}
