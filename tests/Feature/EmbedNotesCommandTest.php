<?php

use App\Jobs\EmbedNoteJob;
use App\Models\Note;
use Illuminate\Support\Facades\Queue;

it('queues an EmbedNoteJob for every note with --all', function () {
    Queue::fake();
    $notes = Note::factory()->count(3)->create();

    $this->artisan('notes:embed', ['--all' => true])->assertSuccessful();

    Queue::assertPushed(EmbedNoteJob::class, 3);

    foreach ($notes as $note) {
        Queue::assertPushed(EmbedNoteJob::class, fn (EmbedNoteJob $job) => $job->noteId === $note->id);
    }
});

it('is safe to re-run: each run queues one job per note', function () {
    Queue::fake();
    Note::factory()->count(2)->create();

    $this->artisan('notes:embed', ['--all' => true])->assertSuccessful();
    $this->artisan('notes:embed', ['--all' => true])->assertSuccessful();

    Queue::assertPushed(EmbedNoteJob::class, 4);
});
