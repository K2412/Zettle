<?php

use App\Ai\Agents\FormulateAgent;
use App\Models\Note;
use App\Models\User;

function fakeFormulate(): void
{
    FormulateAgent::fake([
        'Your title states a topic, not a claim. Consider: "X causes Y because Z."',
    ]);
}

it('returns the prose critique as JSON for the owner', function () {
    fakeFormulate();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('notes.assists.formulate.evaluate', $note), ['draft' => 'A rough draft'])
        ->assertOk()
        ->assertJsonPath('critique', 'Your title states a topic, not a claim. Consider: "X causes Y because Z."')
        ->assertJsonStructure(['critique']);
});

it('forbids evaluating a note owned by another user', function () {
    fakeFormulate();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.formulate.evaluate', $note), ['draft' => 'A rough draft'])
        ->assertForbidden();
});

it('handles an empty draft gracefully', function () {
    fakeFormulate();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('notes.assists.formulate.evaluate', $note), ['draft' => ''])
        ->assertOk()
        ->assertJsonStructure(['critique']);
});

it('handles a missing draft gracefully', function () {
    fakeFormulate();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('notes.assists.formulate.evaluate', $note), [])
        ->assertOk()
        ->assertJsonStructure(['critique']);
});

it('writes nothing when evaluating', function () {
    fakeFormulate();
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'The full raw capture',
    ]);

    $this->actingAs($user)
        ->postJson(route('notes.assists.formulate.evaluate', $note), ['draft' => 'A rough draft'])
        ->assertOk();

    expect($note->fresh())
        ->title->toBe('Origin')
        ->body->toBe('The full raw capture');
});
