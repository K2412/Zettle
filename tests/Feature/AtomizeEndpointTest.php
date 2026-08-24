<?php

use App\Ai\Agents\AtomizeAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use Inertia\Support\SessionKey;

function fakeAtomizeIdeas(): void
{
    AtomizeAgent::fake([
        [
            'ideas' => [
                ['title' => 'Idea one', 'rationale' => 'r1'],
                ['title' => 'Idea two', 'rationale' => 'r2'],
            ],
        ],
    ]);
}

it('returns detected ideas as JSON for the owner', function () {
    fakeAtomizeIdeas();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('notes.assists.atomize', $note))
        ->assertOk()
        ->assertJsonPath('ideas.0.title', 'Idea one')
        ->assertJsonPath('ideas.1.rationale', 'r2')
        ->assertJsonStructure(['ideas' => [['title', 'rationale']]]);
});

it('forbids running atomize on a note owned by another user', function () {
    fakeAtomizeIdeas();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.atomize', $note))
        ->assertForbidden();
});

it('spawns accepted titles, redirects back, and flashes a success toast', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'raw',
        'note_type' => NoteType::Fleeting,
    ]);

    $response = $this->actingAs($user)
        ->from(route('notes.show', $origin))
        ->post(route('notes.assists.atomize.spawn', $origin), [
            'titles' => ['Idea one', 'Idea two'],
        ]);

    $response->assertRedirect(route('notes.show', $origin));

    $spawned = Note::query()->where('note_type', NoteType::Permanent)->get();
    expect($spawned)->toHaveCount(2)
        ->and($spawned->pluck('title')->all())->toBe(['Idea one', 'Idea two']);

    foreach ($spawned as $note) {
        expect(Connection::query()
            ->where('source_note_id', $note->id)
            ->where('target_note_id', $origin->id)
            ->where('relationship', Relationship::Provenance)
            ->exists())->toBeTrue();
    }

    // The success toast is flashed via Inertia::flash, which Inertia surfaces on
    // the next page's top-level `flash` object. Assert it lands in the session
    // flash the redirect carries forward.
    $this->actingAs($user)
        ->from(route('notes.show', $origin))
        ->post(route('notes.assists.atomize.spawn', $origin), ['titles' => ['Idea three']])
        ->assertRedirect(route('notes.show', $origin))
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => '1 note spawned.'],
        ]);
});

it('forbids spawning on a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $origin = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->post(route('notes.assists.atomize.spawn', $origin), ['titles' => ['Idea one']])
        ->assertForbidden();

    expect(Note::query()->where('note_type', NoteType::Permanent)->count())->toBe(0);
});

it('rejects an empty titles array', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $origin))
        ->post(route('notes.assists.atomize.spawn', $origin), ['titles' => []])
        ->assertSessionHasErrors('titles');

    expect(Note::query()->where('note_type', NoteType::Permanent)->count())->toBe(0);
});

it('rejects blank and whitespace-only titles', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $origin))
        ->post(route('notes.assists.atomize.spawn', $origin), ['titles' => ['   ', '']])
        ->assertSessionHasErrors('titles.0');

    expect(Note::query()->where('note_type', NoteType::Permanent)->count())->toBe(0);
});

it('never alters the origin note body when spawning', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'raw',
        'note_type' => NoteType::Fleeting,
    ]);

    $this->actingAs($user)
        ->from(route('notes.show', $origin))
        ->post(route('notes.assists.atomize.spawn', $origin), ['titles' => ['Idea one']]);

    expect($origin->fresh())
        ->title->toBe('Origin')
        ->body->toBe('raw')
        ->note_type->toBe(NoteType::Fleeting);
});
