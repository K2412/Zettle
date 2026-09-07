<?php

namespace App\Jobs;

use App\Services\Note\NoteEmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Clears a note's stored embedding. Dispatched from DeleteNote before the note
 * row is removed, keeping the sqlite-vec table free of orphaned vectors.
 */
class ForgetNoteEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $noteId) {}

    public function handle(NoteEmbeddingService $embeddings): void
    {
        $embeddings->forget($this->noteId);
    }
}
