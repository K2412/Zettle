<?php

use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteClusterService;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

/**
 * Build a hand-known graph whose connected components are obvious by inspection:
 *
 *   Component A: a—b, b—c, a—c   (a triangle of three permanent notes)
 *   Component B: d—e             (a single edge)
 *   Component C: f               (an isolated permanent note, no edges)
 *
 * The expected components below are stated by hand, independent of any BFS.
 *
 * @return array{user: User, notes: array<string, Note>}
 */
function clusterFixture(): array
{
    $user = User::factory()->create();

    $notes = [];
    foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $key) {
        $notes[$key] = Note::factory()->for($user)->create([
            'title' => strtoupper($key),
            'note_type' => NoteType::Permanent,
        ]);
    }

    $edge = function (Note $s, Note $t) use ($user) {
        Connection::factory()->create([
            'user_id' => $user->id,
            'source_note_id' => $s->id,
            'target_note_id' => $t->id,
            'relationship' => Relationship::Supports,
        ]);
    };

    // Component A — triangle.
    $edge($notes['a'], $notes['b']);
    $edge($notes['b'], $notes['c']);
    $edge($notes['a'], $notes['c']);

    // Component B — single edge.
    $edge($notes['d'], $notes['e']);

    // Component C — f left isolated.

    return ['user' => $user, 'notes' => $notes];
}

it('finds the connected components stated by hand', function () {
    ['user' => $user, 'notes' => $n] = clusterFixture();

    $components = app(NoteClusterService::class)->components($user);

    // Represent each component as a sorted set of note ids for comparison.
    $asSets = $components
        ->map(fn ($c) => collect($c['note_ids'])->sort()->values()->all())
        ->sortBy(fn ($ids) => $ids[0])
        ->values()
        ->all();

    $expected = collect([
        [$n['a']->id, $n['b']->id, $n['c']->id],
        [$n['d']->id, $n['e']->id],
        [$n['f']->id],
    ])
        ->map(fn ($ids) => collect($ids)->sort()->values()->all())
        ->sortBy(fn ($ids) => $ids[0])
        ->values()
        ->all();

    expect($asSets)->toBe($expected);
});

it('scores a denser, larger component as riper than a sparser one', function () {
    ['user' => $user, 'notes' => $n] = clusterFixture();

    // Neutralise the vec cohesion term so the test is independent of embeddings:
    // give every component the same cohesion, isolating size × density.
    $service = new NoteClusterService(
        cohesion: fn (array $noteIds) => 1.0,
    );

    $components = $service->components($user);

    $triangle = $components->first(fn ($c) => in_array($n['a']->id, $c['note_ids'], true));
    $singleEdge = $components->first(fn ($c) => in_array($n['d']->id, $c['note_ids'], true));
    $isolated = $components->first(fn ($c) => in_array($n['f']->id, $c['note_ids'], true));

    // The triangle (3 notes, fully connected) must outscore the single edge,
    // which must outscore the isolated note.
    expect($triangle['score'])->toBeGreaterThan($singleEdge['score'])
        ->and($singleEdge['score'])->toBeGreaterThan($isolated['score']);
});

it('only considers the given user\'s notes', function () {
    ['user' => $user] = clusterFixture();
    $other = User::factory()->create();
    Note::factory()->for($other)->create(['note_type' => NoteType::Permanent]);

    $components = app(NoteClusterService::class)->components($user);

    $allIds = $components->flatMap(fn ($c) => $c['note_ids']);
    $otherIds = Note::query()->where('user_id', $other->id)->pluck('id');

    expect($allIds->intersect($otherIds))->toBeEmpty();
});
