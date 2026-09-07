<?php

use App\Ai\Agents\ClusterProjectAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\Assists\ClusterProjectAssist;
use App\Services\Note\NoteClusterService;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

/**
 * A ripe triangle cluster (3 permanent notes, fully connected).
 *
 * @return array{user: User, notes: array<string, Note>}
 */
function projectFixture(): array
{
    $user = User::factory()->create();

    $a = Note::factory()->for($user)->create(['title' => 'Claim A', 'body' => 'A body', 'note_type' => NoteType::Permanent]);
    $b = Note::factory()->for($user)->create(['title' => 'Claim B', 'body' => 'B body', 'note_type' => NoteType::Permanent]);
    $c = Note::factory()->for($user)->create(['title' => 'Claim C', 'body' => 'C body', 'note_type' => NoteType::Permanent]);

    $edge = fn (Note $s, Note $t) => Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $s->id,
        'target_note_id' => $t->id,
        'relationship' => Relationship::Supports,
    ]);

    $edge($a, $b);
    $edge($b, $c);
    $edge($a, $c);

    return ['user' => $user, 'notes' => compact('a', 'b', 'c')];
}

function projectAssist(): ClusterProjectAssist
{
    // Neutralise vec cohesion so ripeness is independent of embeddings.
    return new ClusterProjectAssist(
        new NoteClusterService(cohesion: fn (array $ids) => 1.0),
    );
}

it('surfaces ripe clusters with a readiness assessment and suggested order', function () {
    ['user' => $user, 'notes' => $n] = projectFixture();

    ClusterProjectAgent::fake([
        [
            'assessment' => 'Dense and connected — a draft is latent here.',
            'suggested_order' => [$n['a']->id, $n['c']->id, $n['b']->id],
            'gaps' => ['A note bridging A and B is missing.'],
        ],
    ]);

    $clusters = projectAssist()->run($user);

    expect($clusters)->toHaveCount(1)
        ->and($clusters[0]['assessment'])->toBe('Dense and connected — a draft is latent here.')
        ->and($clusters[0]['suggested_order'])->toBe([$n['a']->id, $n['c']->id, $n['b']->id])
        ->and($clusters[0]['gaps'])->toBe(['A note bridging A and B is missing.']);
});

it('creates a project note that references the cluster notes in order without altering them', function () {
    ['user' => $user, 'notes' => $n] = projectFixture();

    $before = collect($n)->map(fn (Note $note) => $note->only(['title', 'body', 'note_type']))->all();

    $order = [$n['a']->id, $n['c']->id, $n['b']->id];

    $project = projectAssist()->createProjectNote($user, 'My essay', $order);

    expect($project->note_type)->toBe(NoteType::Project)
        ->and($project->user_id)->toBe($user->id);

    // References each permanent note (does not copy it).
    foreach ($order as $id) {
        expect(Connection::query()
            ->where('source_note_id', $project->id)
            ->where('target_note_id', $id)
            ->exists())->toBeTrue();
    }

    // The permanent notes are NOT consumed or rewritten.
    foreach ($n as $key => $note) {
        expect($note->fresh()->only(['title', 'body', 'note_type']))->toBe($before[$key]);
    }
});

it('derives the default project-note title when given an empty title', function () {
    $user = User::factory()->create();

    // The panel passes an empty title; the service supplies the default.
    $project = projectAssist()->createProjectNote($user, '', []);

    expect($project->title)->toBe('Project');
});

it('assesses only the top-ranked cluster eagerly — no N-way synchronous LLM fan-out', function () {
    $user = User::factory()->create();

    // Two independent ripe triangles. The synchronous path must not make one
    // Anthropic call per cluster; it assesses only the top-ranked cluster.
    $makeTriangle = function (string $prefix) use ($user) {
        $x = Note::factory()->for($user)->create(['title' => "$prefix A", 'body' => 'a', 'note_type' => NoteType::Permanent]);
        $y = Note::factory()->for($user)->create(['title' => "$prefix B", 'body' => 'b', 'note_type' => NoteType::Permanent]);
        $z = Note::factory()->for($user)->create(['title' => "$prefix C", 'body' => 'c', 'note_type' => NoteType::Permanent]);
        $edge = fn (Note $s, Note $t) => Connection::factory()->create([
            'user_id' => $user->id,
            'source_note_id' => $s->id,
            'target_note_id' => $t->id,
            'relationship' => Relationship::Supports,
        ]);
        $edge($x, $y);
        $edge($y, $z);
        $edge($x, $z);

        return [$x, $y, $z];
    };

    $makeTriangle('One');
    $makeTriangle('Two');

    $calls = 0;
    ClusterProjectAgent::fake(function () use (&$calls) {
        $calls++;

        return [
            'assessment' => 'A draft is latent here.',
            'suggested_order' => [],
            'gaps' => [],
        ];
    });

    $clusters = projectAssist()->run($user);

    // Both ripe clusters are surfaced, but the LLM was called at most once.
    expect($clusters)->toHaveCount(2)
        ->and($calls)->toBe(1);

    // The top-ranked cluster carries the eager assessment; the rest are unassessed.
    expect($clusters[0]['assessment'])->toBe('A draft is latent here.')
        ->and($clusters[1]['assessment'])->toBe('');
});
