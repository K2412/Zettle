<?php

namespace App\Http\Controllers;

use App\Actions\Note\DeleteNote;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Services\Note\NoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function __construct(private NoteService $notes) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Note::class);

        $search = (string) $request->string('q');
        $tagId = $request->integer('tagId') ?: null;

        return Inertia::render('notes/index', [
            'notes' => $this->notes->listForUser($request->user(), $search, $tagId),
            'tags' => $this->notes->tagsForUser($request->user()),
            'filters' => ['q' => $search, 'tagId' => $tagId],
        ]);
    }

    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        return Inertia::render('notes/show', $this->notes->showData($note));
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        $note = $this->notes->createForUser($request->validated(), $request->user());

        return to_route('notes.show', $note);
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $this->notes->updateWithLinks($note, $request->validated(), $request->user());

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        DeleteNote::run($note);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note deleted.')]);

        return to_route('notes.index');
    }
}
