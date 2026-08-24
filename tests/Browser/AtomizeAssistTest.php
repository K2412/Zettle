<?php

use App\Enums\NoteType;
use App\Models\Note;
use App\Models\User;

it('finds ideas from the faked SDK when Find the ideas is clicked', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-atomize-a1',
        'body' => 'Two distinct ideas live in this note.',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    // Atomize is the wired assist — open its tab, then run rail 1.
    $page->click('@phase-tab-atomize')
        ->click('@atomize-find')
        // The canned fake returns two deterministic ideas (no network).
        ->assertSee('This note holds a first distinct idea')
        ->assertSee('This note holds a second distinct idea');

    // Rail 1 is read-only: the origin note's body is untouched.
    expect($note->fresh()->body)->toBe('Two distinct ideas live in this note.');
});

it('spawns selected ideas, shows a success toast, and stays on the origin', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-atomize-b1',
        'body' => 'seed',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@phase-tab-atomize')
        ->click('@atomize-find')
        ->assertSee('This note holds a first distinct idea')
        // Select the first idea, then spawn.
        ->click('@atomize-idea')
        ->click('@atomize-spawn')
        // Rail 2 write: a success toast surfaces via flash.
        ->assertSee('spawned')
        // Still on the origin note (back() redirect).
        ->assertPathBeginsWith("/notes/{$note->slug}");

    // A permanent note was spawned with a provenance connection to the origin.
    $spawned = Note::query()
        ->where('user_id', $user->id)
        ->where('note_type', NoteType::Permanent)
        ->first();

    expect($spawned)->not->toBeNull()
        ->and($spawned->title)->toBe('This note holds a first distinct idea');

    // The provenance backlink now shows in the origin's connections panel.
    $page->assertSeeIn('@incoming-connections', 'This note holds a first distinct idea');
});
