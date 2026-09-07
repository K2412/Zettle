<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('renders outgoing typed connections grouped by relationship', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center', 'slug' => 'center-p1']);
    $target = Note::factory()->for($user)->create(['title' => 'Grounding', 'slug' => 'grounding-p1']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertPresent('@connections-panel')
        ->assertSeeIn('@connection-group-label', 'supports')
        ->assertSeeIn('@outgoing-connections', 'Grounding');
});

it('renders incoming typed connections with their inverse label', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center', 'slug' => 'center-p2']);
    $source = Note::factory()->for($user)->create(['title' => 'Asserter', 'slug' => 'asserter-p2']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $note->id,
        'relationship' => Relationship::Supports,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@incoming-connections', 'Asserter')
        ->assertSeeIn('@inverse-label', 'supported by');
});

it('keeps the connections panel distinct from the mentions Links and Backlinks', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center', 'slug' => 'center-p3']);
    $mentioned = Note::factory()->for($user)->create(['title' => 'Mentioned', 'slug' => 'mentioned-p3']);
    $typed = Note::factory()->for($user)->create(['title' => 'Supported', 'slug' => 'supported-p3']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $mentioned->id,
        'relationship' => Relationship::Mentions,
    ]);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $typed->id,
        'relationship' => Relationship::Supports,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@outgoing-links', 'Mentioned')
        ->assertSeeIn('@connections-panel', 'Supported');
});

it('navigates from an outgoing connection row to the target note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center', 'slug' => 'center-p4']);
    $target = Note::factory()->for($user)->create(['title' => 'Destination', 'slug' => 'destination-p4']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@connection-link');
    $page->assertPathBeginsWith('/notes/destination-p4');
});
