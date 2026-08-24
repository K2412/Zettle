<?php

use App\Models\Note;
use App\Models\User;

it('renders one tab per phase with the suggested one marked and active', function () {
    $user = User::factory()->create();
    // A fleeting note (the factory default) suggests the Triage phase.
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-panel-a1']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->assertPresent('@assist-panel')
        ->assertPresent('@phase-tab-triage')
        ->assertPresent('@phase-tab-atomize')
        ->assertPresent('@phase-tab-formulate')
        ->assertPresent('@phase-tab-connect')
        ->assertPresent('@phase-tab-make_findable')
        ->assertPresent('@phase-tab-structure')
        ->assertPresent('@phase-tab-cluster_project')
        ->assertSee('· suggested')
        // The suggested phase carries the marker and starts active.
        ->assertAttribute('@phase-tab-triage', 'aria-selected', 'true');
});

it('switches the active child when another phase is clicked, without navigating the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Note', 'slug' => 'note-panel-b1']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->assertAttribute('@phase-tab-triage', 'aria-selected', 'true')
        ->click('@phase-tab-structure')
        // The clicked tab is now active; the suggested one is no longer.
        ->assertAttribute('@phase-tab-structure', 'aria-selected', 'true')
        ->assertAttribute('@phase-tab-triage', 'aria-selected', 'false')
        // An unwired phase shows the coming-soon placeholder.
        ->assertPresent('@phase-placeholder')
        // Tab switching is client-only: still on the origin note.
        ->assertPathBeginsWith("/notes/{$note->slug}");

    // The note body was never touched by a tab switch.
    expect($note->fresh()->body)->toBe($note->body);
});
