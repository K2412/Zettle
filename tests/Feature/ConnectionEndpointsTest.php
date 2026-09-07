<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('stores one directed connection and redirects back', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $source))
        ->post(route('notes.connections.store', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Supports->value,
            'rationale' => 'because it grounds the claim',
        ])
        ->assertRedirect(route('notes.show', $source));

    $connection = Connection::query()->sole();
    expect($connection->source_note_id)->toBe($source->id)
        ->and($connection->target_note_id)->toBe($target->id)
        ->and($connection->relationship)->toBe(Relationship::Supports)
        ->and($connection->rationale)->toBe('because it grounds the claim')
        ->and($connection->user_id)->toBe($user->id);
});

it('allows a connection without a rationale', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('notes.connections.store', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Contradicts->value,
        ])
        ->assertRedirect();

    expect(Connection::query()->sole()->rationale)->toBeNull();
});

it('does not duplicate an identical directed edge', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $payload = [
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports->value,
    ];

    $this->actingAs($user)->post(route('notes.connections.store', $source), $payload);
    $this->actingAs($user)->post(route('notes.connections.store', $source), $payload);

    expect(Connection::query()->count())->toBe(1);
});

it('rejects a mentions relationship', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('notes.connections.store', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Mentions->value,
        ])
        ->assertSessionHasErrors('relationship');

    expect(Connection::query()->count())->toBe(0);
});

it('rejects a provenance relationship', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('notes.connections.store', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Provenance->value,
        ])
        ->assertSessionHasErrors('relationship');

    expect(Connection::query()->count())->toBe(0);
});

it('forbids connecting from a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Note::factory()->for($owner)->create();
    $target = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->post(route('notes.connections.store', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Supports->value,
        ])
        ->assertForbidden();

    expect(Connection::query()->count())->toBe(0);
});

it('forbids connecting to a target note owned by another user', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $foreignTarget = Note::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->post(route('notes.connections.store', $source), [
            'target_note_id' => $foreignTarget->id,
            'relationship' => Relationship::Supports->value,
        ])
        ->assertForbidden();

    expect(Connection::query()->count())->toBe(0);
});

it('edits a connection relationship and rationale in place', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();
    $connection = Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
        'rationale' => 'old reason',
    ]);

    $this->actingAs($user)
        ->patch(route('notes.connections.update', [$source, $connection]), [
            'relationship' => Relationship::Qualifies->value,
            'rationale' => 'new reason',
        ])
        ->assertRedirect();

    $connection->refresh();
    expect($connection->relationship)->toBe(Relationship::Qualifies)
        ->and($connection->rationale)->toBe('new reason');
});

it('forbids editing a connection whose source note belongs to another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Note::factory()->for($owner)->create();
    $target = Note::factory()->for($owner)->create();
    $connection = Connection::factory()->create([
        'user_id' => $owner->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
    ]);

    $this->actingAs($intruder)
        ->patch(route('notes.connections.update', [$source, $connection]), [
            'relationship' => Relationship::Qualifies->value,
        ])
        ->assertForbidden();

    expect($connection->fresh()->relationship)->toBe(Relationship::Supports);
});

it('removes a connection', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();
    $connection = Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
    ]);

    $this->actingAs($user)
        ->delete(route('notes.connections.destroy', [$source, $connection]))
        ->assertRedirect();

    expect(Connection::query()->count())->toBe(0);
});

it('forbids removing a connection whose source note belongs to another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Note::factory()->for($owner)->create();
    $target = Note::factory()->for($owner)->create();
    $connection = Connection::factory()->create([
        'user_id' => $owner->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
    ]);

    $this->actingAs($intruder)
        ->delete(route('notes.connections.destroy', [$source, $connection]))
        ->assertForbidden();

    expect(Connection::query()->count())->toBe(1);
});
