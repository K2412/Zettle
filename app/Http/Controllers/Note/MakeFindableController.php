<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachAssistTagRequest;
use App\Http\Requests\SetDiscoveryHintRequest;
use App\Models\Note;
use App\Services\Note\Assists\MakeFindableAssist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The two-rail Make-findable seam. `run` is the read-only lookup rail. The
 * writes attach a tag or set `discovery_hint` only — never title or body.
 */
class MakeFindableController extends Controller
{
    public function __construct(private MakeFindableAssist $makeFindable) {}

    public function run(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json($this->makeFindable->run($note));
    }

    public function attachTag(AttachAssistTagRequest $request, Note $note): RedirectResponse
    {
        $this->makeFindable->attachTag($note, $request->validated()['name']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tag attached.'),
        ]);

        return back();
    }

    public function setHint(SetDiscoveryHintRequest $request, Note $note): RedirectResponse
    {
        $this->makeFindable->setDiscoveryHint($note, $request->validated()['discovery_hint']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Discovery hint saved.'),
        ]);

        return back();
    }
}
