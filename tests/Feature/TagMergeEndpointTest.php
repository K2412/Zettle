<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;

it('merges the source into the target, remaps notes, removes the source, and flashes success', function () {
    $user = User::factory()->create();
    $source = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);
    $target = Tag::factory()->for($user)->create(['name' => 'machine-learning', 'slug' => 'machine-learning']);

    [$a, $b, $c, $d] = Note::factory()->count(4)->for($user)->create()->all();
    $source->notes()->attach([$a->id, $b->id, $c->id]);
    $target->notes()->attach([$c->id, $d->id]);

    $this->actingAs($user)
        ->from(route('tags.index'))
        ->post(route('tags.merge', $source), ['target_tag_id' => $target->id])
        ->assertRedirect(route('tags.index'));

    expect(Tag::query()->whereKey($source->id)->exists())->toBeFalse()
        ->and($target->notes()->pluck('notes.id')->sort()->values()->all())
        ->toBe([$a->id, $b->id, $c->id, $d->id]);
});

it('rejects merging a tag into itself', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);

    $this->actingAs($user)
        ->from(route('tags.index'))
        ->post(route('tags.merge', $tag), ['target_tag_id' => $tag->id])
        ->assertSessionHasErrors('target_tag_id');

    expect(Tag::query()->whereKey($tag->id)->exists())->toBeTrue();
});

it('forbids merging into a tag owned by another user', function () {
    $user = User::factory()->create();
    $source = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);
    $othersTarget = Tag::factory()->for(User::factory())->create(['name' => 'theirs', 'slug' => 'theirs']);

    $this->actingAs($user)
        ->from(route('tags.index'))
        ->post(route('tags.merge', $source), ['target_tag_id' => $othersTarget->id])
        ->assertSessionHasErrors('target_tag_id');

    expect(Tag::query()->whereKey($source->id)->exists())->toBeTrue();
});

it('forbids merging when the source tag belongs to another user', function () {
    $user = User::factory()->create();
    $othersSource = Tag::factory()->for(User::factory())->create(['name' => 'theirs', 'slug' => 'theirs']);
    $target = Tag::factory()->for($user)->create(['name' => 'mine', 'slug' => 'mine']);

    $this->actingAs($user)
        ->post(route('tags.merge', $othersSource), ['target_tag_id' => $target->id])
        ->assertForbidden();

    expect($othersSource->fresh())->not->toBeNull();
});
