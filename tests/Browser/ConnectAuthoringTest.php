<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('expands an inline form (not a modal) when + Connect is clicked', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'slug' => 'origin-c1']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->assertMissing('@connect-form')
        ->click('@connect-toggle')
        ->assertPresent('@connect-form');
});

it('authors a connection via the search picker and grouped dropdown, and it appears', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'slug' => 'origin-c2']);
    Note::factory()->for($user)->create(['title' => 'Grounding idea', 'slug' => 'grounding-idea-c2']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@connect-toggle')
        ->fill('@connect-search', 'Grounding')
        ->wait(0.6)
        ->click('@connect-result')
        ->assertSeeIn('@connect-target', 'Grounding idea');

    // The relationship dropdown is a native-select-free Radix component; drive it
    // by opening and picking the option.
    $page->click('@connect-relationship')
        ->wait(0.2)
        ->click('supports')
        ->wait(0.2)
        ->click('@connect-save')
        ->wait(0.7);

    $page->assertSeeIn('@connections-panel', 'Grounding idea');

    $connection = Connection::query()->where('source_note_id', $note->id)->sole();
    expect($connection->relationship)->toBe(Relationship::Supports)
        ->and($connection->target->title)->toBe('Grounding idea');
});

it('submits with type and target alone, rationale optional', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'slug' => 'origin-c3']);
    Note::factory()->for($user)->create(['title' => 'Related thought', 'slug' => 'related-thought-c3']);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");
    $page->click('@connect-toggle')
        ->fill('@connect-search', 'Related')
        ->wait(0.6)
        ->click('@connect-result')
        ->click('@connect-relationship')
        ->wait(0.2)
        ->click('contradicts')
        ->wait(0.2)
        ->click('@connect-save')
        ->wait(0.7);

    $connection = Connection::query()->where('source_note_id', $note->id)->sole();
    expect($connection->relationship)->toBe(Relationship::Contradicts)
        ->and($connection->rationale)->toBeNull();
});
