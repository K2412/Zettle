<?php

use App\Enums\NoteType;
use App\Models\Note;
use App\Models\User;

it('casts note_type to the NoteType enum', function () {
    $note = Note::factory()->create(['note_type' => NoteType::Permanent]);

    expect($note->fresh()->note_type)->toBe(NoteType::Permanent);
});

it('can query notes by type', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create(['note_type' => NoteType::Permanent]);
    Note::factory()->for($user)->create(['note_type' => NoteType::Fleeting]);

    $permanent = Note::query()
        ->where('user_id', $user->id)
        ->where('note_type', NoteType::Permanent)
        ->get();

    expect($permanent)->toHaveCount(1)
        ->and($permanent->first()->note_type)->toBe(NoteType::Permanent);
});

it('resolves route bindings by slug', function () {
    $note = Note::factory()->create(['slug' => 'my-note-abc123']);

    expect($note->getRouteKeyName())->toBe('slug');
});

it('links to another note through the connections pivot', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $source->linksTo()->attach($target, [
        'user_id' => $user->id,
        'relationship' => 'mentions',
    ]);

    expect($source->linksTo->pluck('id')->all())->toContain($target->id)
        ->and($target->linkedFrom->pluck('id')->all())->toContain($source->id);
});

it('attaches tags', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();
    $tag = $user->tags()->create(['name' => 'Idea', 'slug' => 'idea', 'color' => '#111111']);

    $note->tags()->attach($tag);

    expect($note->fresh()->tags->pluck('id')->all())->toContain($tag->id);
});
