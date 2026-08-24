<?php

use App\Models\Tag;
use App\Models\User;

it('renames and recolors a tag in one save, re-slugging and lowercasing the color', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'teh', 'slug' => 'teh', 'color' => '#111111']);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'the', 'color' => '#00AAFF'])
        ->assertRedirect();

    $tag->refresh();
    expect($tag->name)->toBe('the')
        ->and($tag->slug)->toBe('the')
        ->and($tag->color)->toBe('#00aaff')
        ->and(Tag::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects an invalid hex color and does not change the tag', function (string $color) {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml', 'color' => '#111111']);

    $this->actingAs($user)
        ->from(route('tags.index'))
        ->patch(route('tags.update', $tag), ['name' => 'ml', 'color' => $color])
        ->assertRedirect(route('tags.index'))
        ->assertSessionHasErrors('color');

    expect($tag->fresh()->color)->toBe('#111111');
})->with(['notacolor', 'red', '#12g', '#fff']);

it('rejects a colliding rename on name with a merge hint, not a silent merge', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'Machine Learning', 'slug' => 'machine-learning']);
    $ml = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);

    $this->actingAs($user)
        ->from(route('tags.index'))
        ->patch(route('tags.update', $ml), ['name' => 'Machine Learning', 'color' => '#00aaff'])
        ->assertRedirect(route('tags.index'))
        ->assertSessionHasErrors('name');

    expect($ml->fresh()->name)->toBe('ml')
        ->and(Tag::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('allows renaming a tag to a slug it already owns (no-op collision with itself)', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'Idea', 'slug' => 'idea']);

    $this->actingAs($user)
        ->patch(route('tags.update', $tag), ['name' => 'idea', 'color' => '#00aaff'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tag->fresh()->name)->toBe('idea');
});

it('forbids updating another user\'s tag', function () {
    $user = User::factory()->create();
    $othersTag = Tag::factory()->for(User::factory())->create(['name' => 'Theirs', 'slug' => 'theirs']);

    $this->actingAs($user)
        ->patch(route('tags.update', $othersTag), ['name' => 'Mine', 'color' => '#00aaff'])
        ->assertForbidden();

    expect($othersTag->fresh()->name)->toBe('Theirs');
});
