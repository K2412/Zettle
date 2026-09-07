<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('belongs to a user and has notes', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();
    $note = Note::factory()->for($user)->create();
    $note->tags()->attach($tag);

    expect($tag->user->is($user))->toBeTrue()
        ->and($tag->notes->pluck('id')->all())->toContain($note->id);
});

it('defaults its color when none is given', function () {
    $user = User::factory()->create();

    $tag = $user->tags()->create(['name' => 'Inbox', 'slug' => 'inbox']);

    expect($tag->fresh()->color)->toBe('#6b7280');
});
