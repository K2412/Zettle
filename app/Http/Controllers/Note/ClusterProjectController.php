<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectNoteRequest;
use App\Models\Note;
use App\Services\Note\Assists\ClusterProjectAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The two-rail Cluster→project seam. `run` is scoped to the signed-in user
 * (ripe clusters across their graph). `create` writes a disposable project
 * note that mentions the cluster — permanent notes are never rewritten.
 */
class ClusterProjectController extends Controller
{
    public function __construct(private ClusterProjectAssist $clusterProject) {}

    public function run(Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json([
            'clusters' => $this->clusterProject->run($request->user()),
        ]);
    }

    public function create(CreateProjectNoteRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();

        $project = $this->clusterProject->createProjectNote(
            $request->user(),
            (string) ($data['title'] ?? ''),
            $data['ordered_note_ids'] ?? [],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Project note created.'),
        ]);

        return to_route('notes.show', $project);
    }
}
