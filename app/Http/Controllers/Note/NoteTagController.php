<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Models\Note;
use App\Models\Tag;
use App\Services\Tag\TagService;
use Illuminate\Http\RedirectResponse;

class NoteTagController extends Controller
{
    public function __construct(private TagService $tags) {}

    /**
     * Attach a tag to the note — either an existing one (`tag_id`) or a new
     * one created from `name`. Note ownership is authorized by StoreTagRequest;
     * a tag owned by someone else is forbidden (403), consistent with a note
     * owned by someone else.
     */
    public function store(StoreTagRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['tag_id'])) {
            $tag = Tag::query()->findOrFail($data['tag_id']);
            abort_unless($tag->user_id === $request->user()->id, 403);

            $note->tags()->syncWithoutDetaching([$tag->id]);
        } else {
            $this->tags->createAndAttachToNote($data, $note, $request->user());
        }

        return back();
    }

    public function destroy(Note $note, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $note);
        abort_unless($tag->user_id === $note->user_id, 403);

        $note->tags()->detach($tag->id);

        return back();
    }
}
