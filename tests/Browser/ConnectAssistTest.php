<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('suggests a typed connection from the faked SDK and links it without rewriting the origin', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-connect-assist-a1',
        'body' => 'A note awaiting a typed link.',
    ]);
    $neighbour = Note::factory()->for($user)->create([
        'title' => 'Neighbour claim',
        'slug' => 'neighbour-connect-assist-a1',
        'body' => 'A related claim.',
    ]);
    embedNotes([$source, $neighbour]);
    $this->actingAs($user);

    $page = visit("/notes/{$source->slug}");

    $page->click('@phase-tab-connect')
        ->click('@connect-assist-run')
        ->assertSee('Neighbour claim')
        ->assertSee('Supports the neighbour by sharing the same claim.')
        ->click('@connect-assist-link')
        ->assertSee('Connection created.');

    expect($source->fresh()->body)->toBe('A note awaiting a typed link.');

    $connection = Connection::query()->where('source_note_id', $source->id)->sole();
    expect($connection->target_note_id)->toBe($neighbour->id)
        ->and($connection->relationship)->toBe(Relationship::Supports);
});
