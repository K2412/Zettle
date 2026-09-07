<?php

use App\Ai\Agents\StructureAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Support\SessionKey;

beforeEach(fn () => Queue::fake());

it('returns a cluster scaffold as JSON for the owner', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Claim A']);

    StructureAgent::fake([
        [
            'central_question' => 'What argument do these notes form together?',
            'scaffold' => "## Core\n- [[Claim A]]",
            'index_entry' => 'A line of thought',
        ],
    ]);

    $this->actingAs($user)
        ->postJson(route('notes.assists.structure', $note))
        ->assertOk()
        ->assertJsonPath('central_question', 'What argument do these notes form together?')
        ->assertJsonPath('index_entry', 'A line of thought')
        ->assertJsonStructure(['cluster_note_ids', 'central_question', 'scaffold', 'index_entry']);
});

it('forbids running structure on a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.structure', $note))
        ->assertForbidden();
});

it('creates a structure note, redirects to it, and leaves the origin untouched', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'raw',
        'note_type' => NoteType::Permanent,
    ]);
    $other = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $origin))
        ->post(route('notes.assists.structure.create', $origin), [
            'title' => 'Structure — Origin',
            'cluster_note_ids' => [$origin->id, $other->id],
        ])
        ->assertRedirect()
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => 'Structure note created.'],
        ]);

    $structure = Note::query()->where('note_type', NoteType::Structure)->first();

    expect($structure)->not->toBeNull()
        ->and($structure->title)->toBe('Structure — Origin')
        ->and($origin->fresh())->title->toBe('Origin')->body->toBe('raw');

    expect(Connection::query()
        ->where('source_note_id', $structure->id)
        ->where('relationship', Relationship::Mentions)
        ->count())->toBe(2);
});
