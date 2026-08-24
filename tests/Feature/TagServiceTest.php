<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\Tag\TagService;

it('creates a user-owned tag and attaches it to the note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $tag = app(TagService::class)->createAndAttachToNote(['name' => 'Inbox'], $note, $user);

    expect($tag->name)->toBe('Inbox')
        ->and($tag->user_id)->toBe($user->id)
        ->and($tag->slug)->toBe('inbox')
        ->and($note->fresh()->tags->pluck('id')->all())->toContain($tag->id);
});

it('reuses an existing tag with the same slug instead of duplicating', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();
    $existing = $user->tags()->create(['name' => 'Idea', 'slug' => 'idea', 'color' => '#111111']);

    $tag = app(TagService::class)->createAndAttachToNote(['name' => 'Idea'], $note, $user);

    expect($tag->id)->toBe($existing->id)
        ->and($user->tags()->count())->toBe(1);
});

it('lists a user\'s tags ordered by name with a usage count', function () {
    $user = User::factory()->create();
    $beta = Tag::factory()->for($user)->create(['name' => 'Beta']);
    Tag::factory()->for($user)->create(['name' => 'Alpha']);
    $beta->notes()->attach(Note::factory()->count(2)->for($user)->create());

    $tags = app(TagService::class)->listForUser($user);

    expect($tags->pluck('name')->all())->toBe(['Alpha', 'Beta'])
        ->and($tags->firstWhere('name', 'Alpha')->notes_count)->toBe(0)
        ->and($tags->firstWhere('name', 'Beta')->notes_count)->toBe(2);
});

it('lists only the acting user\'s tags', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'Mine']);
    Tag::factory()->for(User::factory())->create(['name' => 'Theirs']);

    $tags = app(TagService::class)->listForUser($user);

    expect($tags->pluck('name')->all())->toBe(['Mine']);
});

it('re-slugs on rename and lowercases the color when updating a tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'teh', 'slug' => 'teh', 'color' => '#111111']);

    $updated = app(TagService::class)->update($tag, ['name' => 'the', 'color' => '#00AAFF']);

    expect($updated->name)->toBe('the')
        ->and($updated->slug)->toBe('the')
        ->and($updated->color)->toBe('#00aaff')
        ->and($updated->wasChanged())->toBeTrue()
        ->and(Tag::query()->where('user_id', $user->id)->count())->toBe(1);
});
