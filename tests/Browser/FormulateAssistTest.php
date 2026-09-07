<?php

use App\Models\Note;
use App\Models\User;

it('shows a scaffold template when its type is clicked', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-formulate-a1',
        'body' => 'A note to formulate.',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    // Open the Formulate phase, then pick a scaffold type.
    $page->click('@phase-tab-formulate')
        ->click('@formulate-template-distinction')
        // The client-side template markdown renders, with a copy button.
        ->assertSee('X is not Y — the distinction, stated as a claim')
        ->assertPresent('@formulate-copy-template');

    // Showing a template is client-only: the note body is untouched.
    expect($note->fresh()->body)->toBe('A note to formulate.');
});

it('evaluates a draft from the faked SDK, populating the prose critique', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-formulate-b1',
        'body' => 'A note to formulate.',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@phase-tab-formulate')
        ->fill('@formulate-draft', 'My rough draft about a topic.')
        ->click('@formulate-evaluate')
        // The canned fake returns deterministic prose critique (no network).
        ->assertSee('Your title states a topic, not a claim.')
        ->assertPresent('@formulate-critique');

    // Formulate writes nothing at all: the note body is untouched.
    expect($note->fresh()->body)->toBe('A note to formulate.');
});
