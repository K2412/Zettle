<?php

use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteConnectionDiscoveryService;
use App\Services\Note\NoteEmbeddingService;
use Tests\Support\FakeEmbeddings;

function seedDiscoveryGraph(FakeEmbeddings $fake, User $user): array
{
    $source = Note::factory()->for($user)->create(['title' => 'Source', 'body' => 'about cats']);
    $near = Note::factory()->for($user)->create(['title' => 'Near', 'body' => 'feline friends']);
    $far = Note::factory()->for($user)->create(['title' => 'Far', 'body' => 'tax law']);

    $fake->map('Source'."\n\n".'about cats', [1.0, 0.0])
        ->map('Near'."\n\n".'feline friends', [0.97, 0.03])
        ->map('Far'."\n\n".'tax law', [0.0, 1.0]);

    $service = app(NoteEmbeddingService::class);
    foreach ([$source, $near, $far] as $note) {
        $service->embed($note);
    }

    return [$source, $near, $far];
}

it('returns the nearest OTHER notes ranked by similarity, excluding self', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();
    [$source, $near, $far] = seedDiscoveryGraph($fake, $user);

    $suggestions = app(NoteConnectionDiscoveryService::class)->discover($source);
    $ids = $suggestions->pluck('id');

    expect($ids)->not->toContain($source->id)
        ->and($ids->first())->toBe($near->id)
        ->and($suggestions->first())->toHaveKeys(['id', 'title', 'slug', 'snippet', 'similarity']);
});

it('carries a similarity score for each suggestion', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();
    [$source, $near] = seedDiscoveryGraph($fake, $user);

    $top = app(NoteConnectionDiscoveryService::class)->discover($source)->first();

    expect($top['similarity'])->toBeFloat()->toBeGreaterThan(0.0);
});

it('scopes discovery to the source note owner', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();
    $other = User::factory()->create();

    $source = Note::factory()->for($user)->create(['title' => 'Mine', 'body' => 'topic']);
    $theirs = Note::factory()->for($other)->create(['title' => 'Theirs', 'body' => 'topic']);

    $fake->map('Mine'."\n\n".'topic', [1.0, 0.0])->map('Theirs'."\n\n".'topic', [1.0, 0.0]);
    $service = app(NoteEmbeddingService::class);
    $service->embed($source);
    $service->embed($theirs);

    $ids = app(NoteConnectionDiscoveryService::class)->discover($source)->pluck('id');

    expect($ids)->not->toContain($theirs->id);
});

it('returns empty when the note has no embedding yet', function () {
    FakeEmbeddings::install()->map('unembedded', []);
    $user = User::factory()->create();
    // A note whose text hashes to a vector, but nothing else is embedded, so knn
    // finds no candidates: discovery is empty.
    $source = Note::factory()->for($user)->create(['title' => 'Lonely', 'body' => 'alone']);

    $suggestions = app(NoteConnectionDiscoveryService::class)->discover($source);

    expect($suggestions)->toBeEmpty();
});
