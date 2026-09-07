<?php

use App\Ai\Agents\MakeFindableAgent;
use App\Ai\Agents\NoteTagSuggester;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Support\SessionKey;

beforeEach(fn () => Queue::fake());

function fakeMakeFindable(): void
{
    MakeFindableAgent::fake([
        [
            'retrieval_contexts' => ['When would I reach for this?'],
            'discovery_hint' => 'Reach for this when the claim needs a reminder.',
        ],
    ]);

    NoteTagSuggester::fake([
        ['tags' => ['zettelkasten']],
    ]);
}

it('returns retrieval contexts, a hint, and tags as JSON for the owner', function () {
    fakeMakeFindable();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'body' => 'raw']);

    $this->actingAs($user)
        ->postJson(route('notes.assists.make-findable', $note))
        ->assertOk()
        ->assertJsonPath('discovery_hint', 'Reach for this when the claim needs a reminder.')
        ->assertJsonPath('tags.0', 'zettelkasten')
        ->assertJsonStructure(['retrieval_contexts', 'discovery_hint', 'tags']);

    expect($note->fresh())->title->toBe('Origin')->body->toBe('raw');
});

it('forbids running make-findable on a note owned by another user', function () {
    fakeMakeFindable();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.make-findable', $note))
        ->assertForbidden();
});

it('attaches a suggested tag without rewriting the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'body' => 'raw']);

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.assists.make-findable.attach-tag', $note), ['name' => 'memory'])
        ->assertRedirect(route('notes.show', $note))
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => 'Tag attached.'],
        ]);

    expect($note->fresh()->tags->pluck('name')->all())->toContain('memory')
        ->and($note->fresh())->title->toBe('Origin')->body->toBe('raw');
});

it('sets the discovery hint without rewriting the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Origin', 'body' => 'raw']);

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.assists.make-findable.set-hint', $note), [
            'discovery_hint' => 'Reach for this when X.',
        ])
        ->assertRedirect(route('notes.show', $note))
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => 'Discovery hint saved.'],
        ]);

    expect($note->fresh())
        ->discovery_hint->toBe('Reach for this when X.')
        ->title->toBe('Origin')
        ->body->toBe('raw');
});
