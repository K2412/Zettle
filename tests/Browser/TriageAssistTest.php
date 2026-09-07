<?php

use App\Enums\NoteType;
use App\Models\Note;
use App\Models\User;

it('runs triage from the faked SDK, populating destination, type, and reasoning', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-triage-a1',
        'body' => 'A note awaiting triage.',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    // Triage is the suggested phase for a fleeting note — active on mount.
    $page->click('@triage-run')
        // The canned fake returns a deterministic suggestion (no network).
        ->assertSee('Develop')
        ->assertSee('Permanent')
        ->assertSee('It extends an idea you hold and deserves a permanent note.');

    // Rail 1 is read-only: the origin note's body and type are untouched.
    expect($note->fresh()->body)->toBe('A note awaiting triage.')
        ->and($note->fresh()->note_type)->toBe(NoteType::Fleeting);
});

it('sets the note type from the suggestion, showing a toast and updating the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-triage-b1',
        'body' => 'seed',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@triage-run')
        ->assertSee('Permanent')
        // Rail 2 write: apply the suggested type.
        ->click('@triage-apply')
        // A success toast surfaces via flash.
        ->assertSee('Note type updated.')
        // Still on the origin note (back() redirect).
        ->assertPathBeginsWith("/notes/{$note->slug}");

    // The write set only the note's type, never its body.
    expect($note->fresh()->note_type)->toBe(NoteType::Permanent)
        ->and($note->fresh()->body)->toBe('seed');
});
