<?php

use App\Actions\Tag\MergeTags;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('moves every source note onto the target, dedupes a shared note, and deletes the source', function () {
    $user = User::factory()->create();
    $source = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);
    $target = Tag::factory()->for($user)->create(['name' => 'machine-learning', 'slug' => 'machine-learning']);

    [$a, $b, $c, $d] = Note::factory()->count(4)->for($user)->create()->all();
    $source->notes()->attach([$a->id, $b->id, $c->id]);
    $target->notes()->attach([$c->id, $d->id]);

    $result = MergeTags::run($source, $target);

    expect($result->is($target))->toBeTrue()
        ->and(Tag::query()->whereKey($source->id)->exists())->toBeFalse()
        ->and($target->notes()->pluck('notes.id')->sort()->values()->all())
        ->toBe([$a->id, $b->id, $c->id, $d->id]);

    // The shared note (C) is linked to the target exactly once — no duplicate pivot row.
    expect($target->notes()->wherePivot('note_id', $c->id)->count())->toBe(1);
});

it('guards against merging a tag into itself', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'ml', 'slug' => 'ml']);

    expect(fn () => MergeTags::run($tag, $tag))->toThrow(HttpException::class);
});

it('guards against merging across owners', function () {
    $source = Tag::factory()->for(User::factory())->create(['name' => 'ml', 'slug' => 'ml']);
    $target = Tag::factory()->for(User::factory())->create(['name' => 'ai', 'slug' => 'ai']);

    expect(fn () => MergeTags::run($source, $target))->toThrow(HttpException::class);

    expect(Tag::query()->whereKey($source->id)->exists())->toBeTrue();
});
