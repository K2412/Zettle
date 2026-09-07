<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('attaches an existing tag to the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('notes.tags.store', $note), ['tag_id' => $tag->id])
        ->assertRedirect();

    expect($note->fresh()->tags->pluck('id')->all())->toContain($tag->id);
});

it('detaches a tag from the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();
    $note->tags()->attach($tag);

    $this->actingAs($user)
        ->delete(route('notes.tags.destroy', [$note, $tag]))
        ->assertRedirect();

    expect($note->fresh()->tags->pluck('id')->all())->not->toContain($tag->id);
});

it('creates a new tag and attaches it', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('notes.tags.store', $note), ['name' => 'Inbox'])
        ->assertRedirect();

    $tag = $user->tags()->firstOrFail();
    expect($tag->name)->toBe('Inbox')
        ->and($note->fresh()->tags->pluck('id')->all())->toContain($tag->id);
});

it('validates the new tag name', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $note))
        ->post(route('notes.tags.store', $note), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('forbids attaching a tag to another user\'s note', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();
    $tag = Tag::factory()->for($intruder)->create();

    $this->actingAs($intruder)
        ->post(route('notes.tags.store', $note), ['tag_id' => $tag->id])
        ->assertForbidden();
});

it('forbids attaching another user\'s tag to your own note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();
    $othersTag = Tag::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post(route('notes.tags.store', $note), ['tag_id' => $othersTag->id])
        ->assertForbidden();

    expect($note->fresh()->tags)->toHaveCount(0);
});
