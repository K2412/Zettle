<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('mounts the graph page with a canvas and header counts matching seeded notes and links', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create(['title' => 'Source note', 'slug' => 'source-note-a1']);
    $target = Note::factory()->for($user)->create(['title' => 'Target note', 'slug' => 'target-note-b2']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Mentions,
    ]);
    $this->actingAs($user);

    $page = visit('/notes/graph');

    $page->assertSee('2 notes')
        ->assertSee('1 connection')
        ->assertPresent('@graph-canvas-el');
});

it('shows the empty state and no canvas when there are no notes', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/notes/graph');

    $page->assertSee('No notes to visualize yet')
        ->assertNotPresent('@graph-canvas-el');
});

it('loads the graph page with no JS errors', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Lonely note', 'slug' => 'lonely-note-c3']);
    $this->actingAs($user);

    $page = visit('/notes/graph');

    $page->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});

it('navigates to a note when its disc is clicked on the canvas', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['title' => 'Centered note', 'slug' => 'centered-note-d4']);
    $this->actingAs($user);

    $page = visit('/notes/graph');
    $page->assertPresent('@graph-canvas-el');

    // The hook seeds every node at the canvas centre, so a lone node sits dead-
    // centre from the first frame with no force to move it. Clicking the canvas
    // element (Playwright clicks its centre by default) lands on the disc — the
    // hit is deterministic, not physics-timing-dependent.
    $page->wait(1);
    $page->click('@graph-canvas-el');

    $page->assertPathIs('/notes/centered-note-d4');
});
