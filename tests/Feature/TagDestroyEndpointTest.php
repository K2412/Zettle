<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('deletes the tag and detaches it from its notes, leaving the notes intact', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);
    $notes = Note::factory()->count(3)->for($user)->create();
    $tag->notes()->attach($notes);

    $this->actingAs($user)
        ->from(route('tags.index'))
        ->delete(route('tags.destroy', $tag))
        ->assertRedirect(route('tags.index'));

    expect(Tag::query()->whereKey($tag->id)->exists())->toBeFalse()
        ->and(Note::query()->whereIn('id', $notes->pluck('id'))->count())->toBe(3);

    foreach ($notes as $note) {
        expect($note->tags()->count())->toBe(0);
    }
});

it('forbids deleting another user\'s tag', function () {
    $user = User::factory()->create();
    $othersTag = Tag::factory()->for(User::factory())->create(['name' => 'Theirs', 'slug' => 'theirs']);

    $this->actingAs($user)
        ->delete(route('tags.destroy', $othersTag))
        ->assertForbidden();

    expect($othersTag->fresh())->not->toBeNull();
});
