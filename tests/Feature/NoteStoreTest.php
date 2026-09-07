<?php

use App\Enums\NoteType;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('creates a note and redirects into the editor', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('notes.store'), [
        'title' => 'Idea spark',
        'note_type' => NoteType::Permanent->value,
    ]);

    $note = $user->notes()->firstOrFail();

    expect($note->title)->toBe('Idea spark')
        ->and($note->note_type)->toBe(NoteType::Permanent);
    $response->assertRedirect(route('notes.show', $note));
});

it('defaults an untyped new note to fleeting', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('notes.store'), ['title' => 'Quick capture']);

    expect($user->notes()->firstOrFail()->note_type)->toBe(NoteType::Fleeting);
});

it('requires a title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('notes.index'))
        ->post(route('notes.store'), ['title' => ''])
        ->assertSessionHasErrors('title')
        ->assertRedirect(route('notes.index'));
});

it('rejects an invalid note type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('notes.index'))
        ->post(route('notes.store'), ['title' => 'Fine', 'note_type' => 'not-a-type'])
        ->assertSessionHasErrors('note_type');
});
