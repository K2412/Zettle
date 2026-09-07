<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStructureNoteRequest;
use App\Models\Note;
use App\Services\Note\Assists\StructureAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The two-rail Structure seam. `run` scaffolds a structure note for the
 * origin's cluster. `create` writes a new structure note and mentions edges —
 * the origin's title and body are never touched.
 */
class StructureController extends Controller
{
    public function __construct(private StructureAssist $structure) {}

    public function run(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json($this->structure->run($note));
    }

    public function create(CreateStructureNoteRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();

        $structure = $this->structure->createStructureNote(
            $note,
            (string) ($data['title'] ?? ''),
            $data['cluster_note_ids'] ?? [],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Structure note created.'),
        ]);

        return to_route('notes.show', $structure);
    }
}
