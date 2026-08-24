<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('exposes outgoing typed connections per edge with forward labels and rationale', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center']);
    $target = Note::factory()->for($user)->create(['title' => 'Grounding']);

    // The same pair related two ways — both edges must appear (not distinct-collapsed).
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Supports,
        'rationale' => 'grounds the claim',
    ]);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Extends,
        'rationale' => null,
    ]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->has('connections', 2)
            ->where('connections', function ($connections) {
                $byRel = collect($connections)->keyBy('relationship');

                return $byRel->has('supports')
                    && $byRel->has('extends')
                    && $byRel['supports']['note']['title'] === 'Grounding'
                    && $byRel['supports']['label'] === 'supports'
                    && $byRel['supports']['rationale'] === 'grounds the claim';
            })
        );
});

it('exposes incoming typed connections with the computed inverse label', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center']);
    $source = Note::factory()->for($user)->create(['title' => 'Asserter']);

    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $note->id,
        'relationship' => Relationship::Supports,
        'rationale' => 'why it supports',
    ]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->has('incomingConnections', 1)
            ->where('incomingConnections.0.note.title', 'Asserter')
            ->where('incomingConnections.0.relationship', 'supports')
            ->where('incomingConnections.0.label', 'supported by')
            ->where('incomingConnections.0.rationale', 'why it supports')
        );
});

it('keeps typed edges out of the mentions Links and Backlinks lists', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center']);
    $mentioned = Note::factory()->for($user)->create(['title' => 'Mentioned']);
    $typedTarget = Note::factory()->for($user)->create(['title' => 'Supported']);

    // One mention edge, one authored typed edge from the same note.
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $mentioned->id,
        'relationship' => Relationship::Mentions,
    ]);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $typedTarget->id,
        'relationship' => Relationship::Supports,
    ]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->has('outgoingLinks', 1)
            ->where('outgoingLinks.0.title', 'Mentioned')
            ->has('connections', 1)
            ->where('connections.0.note.title', 'Supported')
        );
});

it('carries the grouped authored relationship vocabulary, system types absent', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->has('relationshipOptions', 3)
            ->where('relationshipOptions.0.group', 'Evidential')
            ->where('relationshipOptions.1.group', 'Structural')
            ->where('relationshipOptions.2.group', 'Dialectical')
            ->where('relationshipOptions.0.options.0.value', 'supports')
            ->where('relationshipOptions.0.options.0.label', 'supports')
        );

    // System types must never appear in the serialized vocabulary.
    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->where('relationshipOptions', function ($options) {
                $values = collect($options)->flatMap(fn ($g) => $g['options'])->pluck('value');

                return ! $values->contains('mentions') && ! $values->contains('provenance');
            })
        );
});
