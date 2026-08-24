<?php

use App\Enums\NoteType;
use App\Enums\Phase;
use App\Enums\Relationship;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the editor with note, links, backlinks, tags and a title map', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Center']);
    $target = Note::factory()->for($user)->create(['title' => 'Outgoing target']);
    $backlink = Note::factory()->for($user)->create(['title' => 'Incoming source']);
    $tag = Tag::factory()->for($user)->create();
    $note->tags()->attach($tag);

    $note->linksTo()->attach($target, ['user_id' => $user->id, 'relationship' => Relationship::Mentions->value]);
    $backlink->linksTo()->attach($note, ['user_id' => $user->id, 'relationship' => Relationship::Mentions->value]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/show')
            ->where('note.title', 'Center')
            ->has('note.tags', 1)
            ->has('outgoingLinks', 1)
            ->where('outgoingLinks.0.title', 'Outgoing target')
            ->has('backlinks', 1)
            ->where('backlinks.0.title', 'Incoming source')
            ->has('availableTags')
            ->has('titleToSlug')
        );
});

it('ships the suggested phase and the full phase vocabulary', function () {
    $user = User::factory()->create();
    // A permanent note with a body but no connections → suggester says Connect.
    $note = Note::factory()->for($user)->create([
        'note_type' => NoteType::Permanent,
        'body' => 'A written claim.',
    ]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/show')
            ->where('suggestedPhase', Phase::Connect->value)
            ->has('phases', 7)
            ->where('phases.0.value', Phase::Triage->value)
            ->where('phases.0.label', 'Triage')
            ->has('phases.0', fn (Assert $option) => $option
                ->where('value', Phase::Triage->value)
                ->where('label', 'Triage')
            )
        );
});

it('suggests Triage for a fresh fleeting note on show', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['note_type' => NoteType::Fleeting]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertInertia(fn (Assert $page) => $page->where('suggestedPhase', Phase::Triage->value));
});

it('forbids viewing a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->get(route('notes.show', $note))
        ->assertForbidden();
});
