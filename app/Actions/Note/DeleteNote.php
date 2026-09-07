<?php

namespace App\Actions\Note;

use App\Jobs\ForgetNoteEmbeddingJob;
use App\Models\Note;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteNote
{
    use AsAction;

    public function handle(Note $note): void
    {
        ForgetNoteEmbeddingJob::dispatch($note->id);

        $note->delete();
    }
}
