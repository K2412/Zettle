<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('edits a connection relationship and rationale in place', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'slug' => 'origin-e1']);
    $target = Note::factory()->for($user)->create(['title' => 'Target', 'slug' => 'target-e1']);
    $connection = Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
        'rationale' => 'old reason',
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@edit-connection')
        ->assertPresent('@connection-edit')
        ->click('@edit-relationship')
        ->wait(0.2)
        ->click('qualifies')
        ->wait(0.2)
        ->fill('@edit-rationale', 'new reason')
        ->click('@edit-save')
        ->wait(0.7);

    $connection->refresh();
    expect($connection->relationship)->toBe(Relationship::Qualifies)
        ->and($connection->rationale)->toBe('new reason');
});

it('removes a connection so it disappears from the panel', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'slug' => 'origin-e2']);
    $target = Note::factory()->for($user)->create(['title' => 'Doomed target', 'slug' => 'doomed-e2']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@connections-panel', 'Doomed target')
        ->click('@remove-connection')
        ->wait(0.7);

    expect(Connection::query()->where('source_note_id', $note->id)->count())->toBe(0);
    $page->assertDontSee('Doomed target');
});

it('does not offer edit or remove on incoming (computed) connections', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center', 'slug' => 'center-e3']);
    $source = Note::factory()->for($user)->create(['title' => 'Asserter', 'slug' => 'asserter-e3']);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $note->id,
        'relationship' => Relationship::Supports,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertSeeIn('@incoming-connections', 'Asserter')
        ->assertMissing('@edit-connection')
        ->assertMissing('@remove-connection');
});
