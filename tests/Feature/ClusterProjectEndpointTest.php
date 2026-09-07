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
use Inertia\Support\SessionKey;

beforeEach(fn () => Queue::fake());

function bindNeutralClusterProject(): void
{
    app()->instance(
        ClusterProjectAssist::class,
        new ClusterProjectAssist(new NoteClusterService(cohesion: fn (array $ids) => 1.0)),
    );
}

it('returns ripe clusters as JSON for the owner', function () {
    bindNeutralClusterProject();

    $user = User::factory()->create();
    $a = Note::factory()->for($user)->create(['title' => 'A', 'note_type' => NoteType::Permanent]);
    $b = Note::factory()->for($user)->create(['title' => 'B', 'note_type' => NoteType::Permanent]);
    $c = Note::factory()->for($user)->create(['title' => 'C', 'note_type' => NoteType::Permanent]);

    foreach ([[$a, $b], [$b, $c], [$a, $c]] as [$from, $to]) {
        Connection::factory()->create([
            'user_id' => $user->id,
            'source_note_id' => $from->id,
            'target_note_id' => $to->id,
            'relationship' => Relationship::Supports,
        ]);
    }

    ClusterProjectAgent::fake([
        [
            'assessment' => 'The cluster is dense enough to harvest into a project.',
            'suggested_order' => [$a->id, $b->id, $c->id],
            'gaps' => ['A bridging note is still missing.'],
        ],
    ]);

    $this->actingAs($user)
        ->postJson(route('notes.assists.cluster-project', $a))
        ->assertOk()
        ->assertJsonPath('clusters.0.assessment', 'The cluster is dense enough to harvest into a project.')
        ->assertJsonStructure(['clusters' => [['note_ids', 'score', 'assessment', 'suggested_order', 'gaps']]]);
});

it('forbids running cluster-project on a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.cluster-project', $note))
        ->assertForbidden();
});

it('creates a project note, redirects to it, and leaves permanent notes untouched', function () {
    $user = User::factory()->create();
    $a = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'raw',
        'note_type' => NoteType::Permanent,
    ]);
    $b = Note::factory()->for($user)->create(['note_type' => NoteType::Permanent]);

    $this->actingAs($user)
        ->from(route('notes.show', $a))
        ->post(route('notes.assists.cluster-project.create', $a), [
            'title' => 'My project',
            'ordered_note_ids' => [$a->id, $b->id],
        ])
        ->assertRedirect()
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => 'Project note created.'],
        ]);

    $project = Note::query()->where('note_type', NoteType::Project)->first();

    expect($project)->not->toBeNull()
        ->and($project->title)->toBe('My project')
        ->and($a->fresh())->title->toBe('Origin')->body->toBe('raw');
});
