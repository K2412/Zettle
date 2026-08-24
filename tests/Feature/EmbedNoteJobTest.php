<?php

use App\Actions\Note\DeleteNote;
use App\Jobs\EmbedNoteJob;
use App\Jobs\ForgetNoteEmbeddingJob;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteEmbeddingService;
use App\Services\Note\NoteService;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeEmbeddings;

function storedEmbeddingCount(int $noteId): int
{
    return (int) DB::selectOne(
        'SELECT count(*) AS c FROM note_embeddings WHERE note_id = ?',
        [$noteId],
    )->c;
}

it('embeds the note when the EmbedNoteJob runs', function () {
    FakeEmbeddings::install();
    $note = Note::factory()->create(['title' => 'Runnable', 'body' => 'content']);

    (new EmbedNoteJob($note->id))->handle(app(NoteEmbeddingService::class));

    expect(storedEmbeddingCount($note->id))->toBe(1);
});

it('forgets the note when the ForgetNoteEmbeddingJob runs', function () {
    FakeEmbeddings::install();
    $note = Note::factory()->create(['title' => 'Doomed', 'body' => 'content']);
    $service = app(NoteEmbeddingService::class);
    $service->embed($note);
    expect(storedEmbeddingCount($note->id))->toBe(1);

    (new ForgetNoteEmbeddingJob($note->id))->handle($service);

    expect(storedEmbeddingCount($note->id))->toBe(0);
});

it('embeds a note when its body changes on save (sync queue)', function () {
    FakeEmbeddings::install();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Save me', 'body' => '']);

    app(NoteService::class)->updateWithLinks($note, ['body' => 'now with body'], $user);

    expect(storedEmbeddingCount($note->id))->toBe(1);
});

it('removes the embedding when the note is deleted (sync queue)', function () {
    FakeEmbeddings::install();
    $note = Note::factory()->create(['title' => 'Delete me', 'body' => 'text']);
    app(NoteEmbeddingService::class)->embed($note);
    expect(storedEmbeddingCount($note->id))->toBe(1);

    DeleteNote::run($note);

    expect(storedEmbeddingCount($note->id))->toBe(0);
});
