<?php

use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteEmbeddingService;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeEmbeddings;

function embeddingRowCount(int $noteId): int
{
    return (int) DB::selectOne(
        'SELECT count(*) AS c FROM note_embeddings WHERE note_id = ?',
        [$noteId],
    )->c;
}

it('stores a 1536-vector for the note when embedding', function () {
    $fake = FakeEmbeddings::install();
    $fake->map("Alpha\n\nThe body", [1.0, 0.0, 0.0]);

    $note = Note::factory()->create(['title' => 'Alpha', 'body' => 'The body']);

    app(NoteEmbeddingService::class)->embed($note);

    expect(embeddingRowCount($note->id))->toBe(1);
});

it('forgets a note by removing its stored embedding', function () {
    FakeEmbeddings::install();
    $note = Note::factory()->create(['title' => 'Gamma', 'body' => 'text']);

    $service = app(NoteEmbeddingService::class);
    $service->embed($note);
    expect(embeddingRowCount($note->id))->toBe(1);

    $service->forget($note->id);

    expect(embeddingRowCount($note->id))->toBe(0);
});

it('returns nearest note_ids by distance from knn', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();

    $onXAxis = Note::factory()->for($user)->create(['title' => 'X', 'body' => '']);
    $onYAxis = Note::factory()->for($user)->create(['title' => 'Y', 'body' => '']);
    $nearX = Note::factory()->for($user)->create(['title' => 'NearX', 'body' => '']);

    $fake->map('X', [1.0, 0.0])
        ->map('Y', [0.0, 1.0])
        ->map('NearX', [0.92, 0.08]);

    $service = app(NoteEmbeddingService::class);
    foreach ([$onXAxis, $onYAxis, $nearX] as $note) {
        $service->embed($note);
    }

    $results = $service->knn($fake->vectorFor('X'), $user->id, 3);

    expect($results->pluck('note_id')->take(2)->all())
        ->toBe([$onXAxis->id, $nearX->id]);
});

it('scopes knn to the given user', function () {
    $fake = FakeEmbeddings::install();
    $mine = User::factory()->create();
    $theirs = User::factory()->create();

    $myNote = Note::factory()->for($mine)->create(['title' => 'Mine', 'body' => '']);
    $theirNote = Note::factory()->for($theirs)->create(['title' => 'Theirs', 'body' => '']);

    $fake->map('Mine', [1.0, 0.0])->map('Theirs', [1.0, 0.0]);

    $service = app(NoteEmbeddingService::class);
    $service->embed($myNote);
    $service->embed($theirNote);

    $results = $service->knn($fake->vectorFor('Mine'), $mine->id, 10);

    expect($results->pluck('note_id')->all())->toBe([$myNote->id]);
});

it('forgets rather than embeds a note with empty title and body', function () {
    FakeEmbeddings::install();
    $note = Note::factory()->create(['title' => '', 'body' => '']);

    app(NoteEmbeddingService::class)->embed($note);

    expect(embeddingRowCount($note->id))->toBe(0);
});

it('generates a raw vector for arbitrary text', function () {
    $fake = FakeEmbeddings::install();
    $fake->map('some text', [0.5, 0.5]);

    $vector = app(NoteEmbeddingService::class)->generateVector('some text');

    expect($vector)->toBeArray()->toHaveCount(1536);
});
