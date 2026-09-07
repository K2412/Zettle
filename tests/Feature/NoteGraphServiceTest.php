<?php

use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\Note\NoteGraphService;
use Illuminate\Support\Facades\DB;

it('shapes each note into a node with its first tag color', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['color' => '#ff0000']);
    $note = Note::factory()->for($user)->create(['title' => 'Alpha', 'slug' => 'alpha-1']);
    $note->tags()->attach($tag);

    $graph = app(NoteGraphService::class)->buildGraphData($user);

    expect($graph['nodes'])->toHaveCount(1)
        ->and($graph['nodes'][0])->toBe([
            'id' => $note->id,
            'title' => 'Alpha',
            'slug' => 'alpha-1',
            'color' => '#ff0000',
        ]);
});

it('falls back to grey when a note has no tags', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->create();

    $graph = app(NoteGraphService::class)->buildGraphData($user);

    expect($graph['nodes'][0]['color'])->toBe('#6b7280');
});

it('classifies a mentions connection as a mention edge and a typed connection as typed', function () {
    $user = User::factory()->create();
    $a = Note::factory()->for($user)->create();
    $b = Note::factory()->for($user)->create();
    $c = Note::factory()->for($user)->create();
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $a->id,
        'target_note_id' => $b->id,
        'relationship' => Relationship::Mentions,
    ]);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $a->id,
        'target_note_id' => $c->id,
        'relationship' => Relationship::Supports,
    ]);

    $graph = app(NoteGraphService::class)->buildGraphData($user);

    expect($graph['edges'])->toContain([
        'source' => $a->id,
        'target' => $b->id,
        'kind' => 'mention',
    ])->toContain([
        'source' => $a->id,
        'target' => $c->id,
        'kind' => 'typed',
    ]);
});

it('collapses two typed connections between the same pair into one typed edge', function () {
    $user = User::factory()->create();
    $a = Note::factory()->for($user)->create();
    $b = Note::factory()->for($user)->create();
    // Two distinct authored relationships between the same pair both bucket to 'typed'.
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $a->id,
        'target_note_id' => $b->id,
        'relationship' => Relationship::Supports,
    ]);
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $a->id,
        'target_note_id' => $b->id,
        'relationship' => Relationship::Extends,
    ]);

    $graph = app(NoteGraphService::class)->buildGraphData($user);

    $typedBetweenPair = array_values(array_filter(
        $graph['edges'],
        fn ($edge) => $edge['source'] === $a->id && $edge['target'] === $b->id && $edge['kind'] === 'typed',
    ));

    expect($typedBetweenPair)->toHaveCount(1);
});

it('excludes another user\'s notes and edges', function () {
    $user = User::factory()->create();
    $mine = Note::factory()->for($user)->create(['title' => 'Mine']);

    $other = User::factory()->create();
    $theirsA = Note::factory()->for($other)->create(['title' => 'Theirs A']);
    $theirsB = Note::factory()->for($other)->create(['title' => 'Theirs B']);
    Connection::factory()->create([
        'user_id' => $other->id,
        'source_note_id' => $theirsA->id,
        'target_note_id' => $theirsB->id,
        'relationship' => Relationship::Supports,
    ]);

    $graph = app(NoteGraphService::class)->buildGraphData($user);

    expect($graph['nodes'])->toHaveCount(1)
        ->and($graph['nodes'][0]['id'])->toBe($mine->id)
        ->and($graph['edges'])->toBe([]);
});

it('builds the graph in a bounded number of queries regardless of note count', function () {
    $user = User::factory()->create();
    $notes = Note::factory()->for($user)->count(5)->create();
    $tag = Tag::factory()->for($user)->create();
    foreach ($notes as $note) {
        $note->tags()->attach($tag);
    }
    Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $notes[0]->id,
        'target_note_id' => $notes[1]->id,
        'relationship' => Relationship::Mentions,
    ]);

    DB::enableQueryLog();
    app(NoteGraphService::class)->buildGraphData($user);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // One read for the notes plus one per eager-loaded relation (linksTo, tags) —
    // constant, not one-per-note.
    expect($queryCount)->toBeLessThanOrEqual(3);
});
