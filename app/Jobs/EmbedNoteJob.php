<?php

namespace App\Jobs;

use App\Models\Note;
use App\Services\Note\NoteEmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Embeds a note's title+body into sqlite-vec. Dispatched from
 * NoteService::updateWithLinks whenever the title or body changes. A note
 * deleted before the job runs is simply skipped.
 */
class EmbedNoteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $noteId) {}

    public function handle(NoteEmbeddingService $embeddings): void
    {
        $note = Note::find($this->noteId);

        if ($note === null) {
            return;
        }

        $embeddings->embed($note);
    }
}
