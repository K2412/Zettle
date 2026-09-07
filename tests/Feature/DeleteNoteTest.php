<?php

use App\Actions\Note\DeleteNote;
use App\Jobs\ForgetNoteEmbeddingJob;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('deletes the note and dispatches the forget-embedding job', function () {
    Queue::fake();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    DeleteNote::run($note);

    expect(Note::query()->whereKey($note->id)->exists())->toBeFalse();
    Queue::assertPushed(ForgetNoteEmbeddingJob::class);
});
