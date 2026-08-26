<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Services\Note\Assists\FormulateAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The read-only Formulate seam. `evaluate` is the lookup rail only — a POST (AI
 * generation is non-idempotent and billable, so it is not a prefetchable GET)
 * that authorizes viewing and returns prose critique as JSON for a background
 * fetch. Formulate has NO write rail: it never persists anything, and never
 * touches the note's title or body (ADR-0005).
 */
class FormulateController extends Controller
{
    public function __construct(private FormulateAssist $formulate) {}

    public function evaluate(Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $validated = $request->validate([
            'draft' => ['nullable', 'string'],
        ]);

        return response()->json([
            'critique' => $this->formulate->evaluate($note, (string) ($validated['draft'] ?? '')),
        ]);
    }
}
