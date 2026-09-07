<?php

use App\Ai\Agents\StructureAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\Assists\StructureAssist;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

/**
 * A three-note cluster (a—b—c triangle) plus the isolated note the user is on.
 *
 * @return array{user: User, cluster: array<string, Note>}
 */
function structureFixture(): array
{
    $user = User::factory()->create();

    $a = Note::factory()->for($user)->create(['title' => 'Claim A', 'note_type' => NoteType::Permanent]);
    $b = Note::factory()->for($user)->create(['title' => 'Claim B', 'note_type' => NoteType::Permanent]);
    $c = Note::factory()->for($user)->create(['title' => 'Claim C', 'note_type' => NoteType::Permanent]);

    $edge = fn (Note $s, Note $t) => Connection::factory()->create([
        'user_id' => $user->id,
        'source_note_id' => $s->id,
        'target_note_id' => $t->id,
        'relationship' => Relationship::Supports,
    ]);

    $edge($a, $b);
    $edge($b, $c);
    $edge($a, $c);

    return ['user' => $user, 'cluster' => compact('a', 'b', 'c')];
}

it('returns the cluster the note belongs to and a scaffold that references those notes', function () {
    ['cluster' => $cluster] = structureFixture();
    $a = $cluster['a'];

    StructureAgent::fake([
        [
            'central_question' => 'How do A, B, and C fit together?',
            'scaffold' => "# Structure — How A, B, C relate\n\n## Core\n- [[Claim A]]\n- [[Claim B]]\n- [[Claim C]]\n\n## Open questions\n- Is C really downstream of A?",
            'index_entry' => 'A/B/C line of thought',
        ],
    ]);

    $result = app(StructureAssist::class)->run($a);

    expect($result['cluster_note_ids'])->toEqualCanonicalizing([
        $cluster['a']->id, $cluster['b']->id, $cluster['c']->id,
    ]);

    foreach (['Claim A', 'Claim B', 'Claim C'] as $title) {
        expect($result['scaffold'])->toContain($title);
    }

    expect($result['index_entry'])->toBe('A/B/C line of thought');
});

it('creates a structure note referencing the cluster and leaves the cluster notes unchanged', function () {
    ['user' => $user, 'cluster' => $cluster] = structureFixture();
    $a = $cluster['a'];

    $before = [
        $cluster['a']->only(['title', 'body', 'note_type']),
        $cluster['b']->only(['title', 'body', 'note_type']),
        $cluster['c']->only(['title', 'body', 'note_type']),
    ];

    $structure = app(StructureAssist::class)->createStructureNote(
        $a,
        'Structure — A/B/C',
        [$cluster['a']->id, $cluster['b']->id, $cluster['c']->id],
    );

    expect($structure->note_type)->toBe(NoteType::Structure)
        ->and($structure->user_id)->toBe($user->id);

    // The structure note references (mentions) each cluster note.
    foreach ($cluster as $note) {
        expect(Connection::query()
            ->where('source_note_id', $structure->id)
            ->where('target_note_id', $note->id)
            ->exists())->toBeTrue();
    }

    // The cluster notes themselves are untouched.
    expect($cluster['a']->fresh()->only(['title', 'body', 'note_type']))->toBe($before[0]);
    expect($cluster['b']->fresh()->only(['title', 'body', 'note_type']))->toBe($before[1]);
    expect($cluster['c']->fresh()->only(['title', 'body', 'note_type']))->toBe($before[2]);
});

it('derives the default structure-note title when given an empty title', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create(['title' => 'Origin claim', 'note_type' => NoteType::Permanent]);

    // The panel passes an empty title; the service supplies the default.
    $structure = app(StructureAssist::class)->createStructureNote($origin, '', []);

    expect($structure->title)->toBe('Structure — Origin claim');
});

it('makes a single LLM call for the note cluster — no per-cluster synchronous fan-out', function () {
    ['cluster' => $cluster] = structureFixture();

    // Multiple other ripe clusters exist for the same user, but run() must map
    // only the cluster the current note belongs to — one Anthropic call.
    $other = structureFixture()['cluster'];

    $calls = 0;
    StructureAgent::fake(function () use (&$calls) {
        $calls++;

        return [
            'central_question' => 'q',
            'scaffold' => 's',
            'index_entry' => '',
        ];
    });

    app(StructureAssist::class)->run($cluster['a']);

    expect($calls)->toBe(1);
});
