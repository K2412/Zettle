<?php

namespace App\Http\Controllers\Note;

use App\Enums\Relationship;
use App\Http\Controllers\Controller;
use App\Http\Requests\LinkAssistRequest;
use App\Models\Note;
use App\Services\Note\Assists\ConnectAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The two-rail Connect seam. `run` is the read-only lookup rail: a POST that
 * authorizes viewing and returns typed-connection suggestions as JSON. `link`
 * is the write rail: an Inertia visit that creates one typed connection and
 * redirects back. Neither rail ever writes the origin note's title or body.
 */
class ConnectController extends Controller
{
    public function __construct(private ConnectAssist $connect) {}

    public function run(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $candidates = array_map(function (array $candidate): array {
            return [
                ...$candidate,
                'relationship' => $candidate['relationship']->value,
            ];
        }, $this->connect->run($note));

        return response()->json(['candidates' => $candidates]);
    }

    public function link(LinkAssistRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();

        $target = Note::query()->findOrFail($data['target_note_id']);
        abort_unless($target->user_id === $request->user()->id, 403);

        $this->connect->link(
            $note,
            $target,
            Relationship::from($data['relationship']),
            $data['rationale'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Connection created.'),
        ]);

        return back();
    }
}
