<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('casts relationship to the Relationship enum and links source to target', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $connection = Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $source->id,
        'target_note_id' => $target->id,
        'relationship' => Relationship::Mentions,
    ]);

    expect($connection->fresh()->relationship)->toBe(Relationship::Mentions)
        ->and($connection->source->is($source))->toBeTrue()
        ->and($connection->target->is($target))->toBeTrue();
});

it('reads the relationship back as an enum through the pivot', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    $source->linksTo()->attach($target, [
        'user_id' => $user->id,
        'relationship' => Relationship::Mentions->value,
    ]);

    $pivot = $source->fresh()->linksTo->first()->pivot;

    expect($pivot->relationship)->toBe(Relationship::Mentions);
});
