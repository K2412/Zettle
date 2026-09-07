<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyConnectionRequest;
use App\Http\Requests\StoreConnectionRequest;
use App\Http\Requests\UpdateConnectionRequest;
use App\Models\Connection;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;

class ConnectionController extends Controller
{
    /**
     * Author one directed typed connection from the note to a target the user
     * owns. Ownership of the source note is authorized by the Form Request; the
     * target's ownership is checked here (403, consistent with tag auth). The
     * relationship is validated to the authored set, so a `mentions` row is never
     * written through this channel — wikilink sync alone owns that.
     */
    public function store(StoreConnectionRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();

        $target = Note::query()->findOrFail($data['target_note_id']);
        abort_unless($target->user_id === $request->user()->id, 403);

        Connection::query()->firstOrCreate([
            'source_note_id' => $note->id,
            'target_note_id' => $target->id,
            'relationship' => $data['relationship'],
        ], [
            'user_id' => $note->user_id,
            'rationale' => $data['rationale'] ?? null,
        ]);

        return back();
    }

    public function update(UpdateConnectionRequest $request, Note $note, Connection $connection): RedirectResponse
    {
        abort_unless($connection->source_note_id === $note->id, 403);

        $connection->update($request->validated());

        return back();
    }

    public function destroy(DestroyConnectionRequest $request, Note $note, Connection $connection): RedirectResponse
    {
        abort_unless($connection->source_note_id === $note->id, 403);

        $connection->delete();

        return back();
    }
}
