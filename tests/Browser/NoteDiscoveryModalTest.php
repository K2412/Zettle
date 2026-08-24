<?php

use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteEmbeddingService;

function embedNotes(iterable $notes): void
{
    $service = app(NoteEmbeddingService::class);
    foreach ($notes as $note) {
        $service->embed($note);
    }
}

it('opens the Find connections modal and renders ranked suggestions on demand', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create(['title' => 'Source note', 'slug' => 'source-note-a1', 'body' => 'seed']);
    $candidate = Note::factory()->for($user)->create(['title' => 'Candidate note', 'slug' => 'candidate-note-b2', 'body' => 'other']);
    embedNotes([$source, $candidate]);
    $this->actingAs($user);

    $page = visit('/notes/source-note-a1');

    $page->click('@find-connections')
        ->assertSee('Candidate note');
});

it('clicking a suggestion opens the +Connect form pre-filled with that target', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create(['title' => 'Source note', 'slug' => 'source-note-a1', 'body' => 'seed']);
    $candidate = Note::factory()->for($user)->create(['title' => 'Candidate note', 'slug' => 'candidate-note-b2', 'body' => 'other']);
    embedNotes([$source, $candidate]);
    $this->actingAs($user);

    $page = visit('/notes/source-note-a1');

    $page->click('@find-connections')
        ->click('@discovery-suggestion')
        ->assertSee('Candidate note')
        ->assertPresent('@connect-target');

    // No edge is created just by picking a target.
    expect($source->outgoingConnections()->count())->toBe(0);
});
