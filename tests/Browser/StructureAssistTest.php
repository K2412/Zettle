<?php

use App\Enums\NoteType;
use App\Models\Note;
use App\Models\User;

it('scaffolds a structure note from the faked SDK without rewriting the origin', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-structure-a1',
        'body' => 'A claim waiting for a structure note.',
        'note_type' => NoteType::Permanent,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@phase-tab-structure')
        ->click('@structure-run')
        ->assertSee('What argument do these notes form together?')
        ->assertSee('Related notes belong here.');

    expect($note->fresh()->body)->toBe('A claim waiting for a structure note.')
        ->and($note->fresh()->note_type)->toBe(NoteType::Permanent);
});

it('creates the structure note and leaves the origin untouched', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'slug' => 'origin-structure-b1',
        'body' => 'seed',
        'note_type' => NoteType::Permanent,
    ]);
    $this->actingAs($user);

    $page = visit("/notes/{$note->slug}");

    $page->click('@phase-tab-structure')
        ->click('@structure-run')
        ->click('@structure-create')
        ->assertSee('Structure note created.');

    $structure = Note::query()->where('note_type', NoteType::Structure)->first();

    expect($structure)->not->toBeNull()
        ->and($note->fresh()->body)->toBe('seed')
        ->and($note->fresh()->title)->toBe('Origin');

    $page->assertPathBeginsWith('/notes/'.$structure->slug);
});
