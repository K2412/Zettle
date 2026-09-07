<?php

use App\Ai\Agents\TriageAgent;
use App\Enums\NoteType;
use App\Models\Note;
use App\Models\User;
use Inertia\Support\SessionKey;

function fakeTriage(): void
{
    TriageAgent::fake([
        [
            'destination' => 'develop',
            'note_type' => 'permanent',
            'reasoning' => 'It extends an idea you hold.',
        ],
    ]);
}

it('returns the triage suggestion as JSON for the owner', function () {
    fakeTriage();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('notes.assists.triage', $note))
        ->assertOk()
        ->assertJsonPath('destination', 'develop')
        ->assertJsonPath('note_type', 'permanent')
        ->assertJsonPath('reasoning', 'It extends an idea you hold.')
        ->assertJsonStructure(['destination', 'note_type', 'reasoning']);
});

it('forbids running triage on a note owned by another user', function () {
    fakeTriage();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.triage', $note))
        ->assertForbidden();
});

it('applies a valid note type, redirects back, and flashes a success toast', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['note_type' => NoteType::Fleeting]);

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.assists.triage.apply-type', $note), ['note_type' => 'permanent'])
        ->assertRedirect(route('notes.show', $note))
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => 'Note type updated.'],
        ]);

    expect($note->fresh()->note_type)->toBe(NoteType::Permanent);
});

it('forbids applying a note type on a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create(['note_type' => NoteType::Fleeting]);

    $this->actingAs($intruder)
        ->post(route('notes.assists.triage.apply-type', $note), ['note_type' => 'permanent'])
        ->assertForbidden();

    expect($note->fresh()->note_type)->toBe(NoteType::Fleeting);
});

it('rejects a missing note type', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.assists.triage.apply-type', $note), [])
        ->assertSessionHasErrors('note_type');
});

it('rejects an invalid note type', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.assists.triage.apply-type', $note), ['note_type' => 'not-a-type'])
        ->assertSessionHasErrors('note_type');
});

it('never alters the note title or body when applying a type', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'The full raw capture',
        'note_type' => NoteType::Fleeting,
    ]);

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.assists.triage.apply-type', $note), ['note_type' => 'permanent']);

    expect($note->fresh())
        ->title->toBe('Origin')
        ->body->toBe('The full raw capture')
        ->note_type->toBe(NoteType::Permanent);
});
