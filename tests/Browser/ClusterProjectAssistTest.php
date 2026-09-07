<?php

use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;

it('finds a ripe cluster from the faked SDK without rewriting the origin', function () {
    $user = User::factory()->create();
    $a = Note::factory()->for($user)->create([
        'title' => 'Claim A',
        'slug' => 'claim-a-cluster-a1',
        'body' => 'seed',
        'note_type' => NoteType::Permanent,
    ]);
    $b = Note::factory()->for($user)->create([
        'title' => 'Claim B',
        'slug' => 'claim-b-cluster-a1',
        'note_type' => NoteType::Permanent,
    ]);
    $c = Note::factory()->for($user)->create([
        'title' => 'Claim C',
        'slug' => 'claim-c-cluster-a1',
        'note_type' => NoteType::Permanent,
    ]);

    foreach ([[$a, $b], [$b, $c], [$a, $c]] as [$from, $to]) {
        Connection::factory()->create([
            'user_id' => $user->id,
            'source_note_id' => $from->id,
            'target_note_id' => $to->id,
            'relationship' => Relationship::Supports,
        ]);
    }

    $this->actingAs($user);

    $page = visit("/notes/{$a->slug}");

    $page->click('@phase-tab-cluster_project')
        ->click('@cluster-project-run')
        ->assertSee('The cluster is dense enough to harvest into a project.')
        ->assertSee('A bridging note is still missing.');

    expect($a->fresh()->body)->toBe('seed')
        ->and($a->fresh()->note_type)->toBe(NoteType::Permanent);
});

it('creates a project note from a ripe cluster and leaves permanent notes untouched', function () {
    $user = User::factory()->create();
    $a = Note::factory()->for($user)->create([
        'title' => 'Claim A',
        'slug' => 'claim-a-cluster-b1',
        'body' => 'seed',
        'note_type' => NoteType::Permanent,
    ]);
    $b = Note::factory()->for($user)->create([
        'title' => 'Claim B',
        'slug' => 'claim-b-cluster-b1',
        'note_type' => NoteType::Permanent,
    ]);
    $c = Note::factory()->for($user)->create([
        'title' => 'Claim C',
        'slug' => 'claim-c-cluster-b1',
        'note_type' => NoteType::Permanent,
    ]);

    foreach ([[$a, $b], [$b, $c], [$a, $c]] as [$from, $to]) {
        Connection::factory()->create([
            'user_id' => $user->id,
            'source_note_id' => $from->id,
            'target_note_id' => $to->id,
            'relationship' => Relationship::Supports,
        ]);
    }

    $this->actingAs($user);

    $page = visit("/notes/{$a->slug}");

    $page->click('@phase-tab-cluster_project')
        ->click('@cluster-project-run')
        ->click('@cluster-project-create')
        ->assertSee('Project note created.');

    $project = Note::query()->where('note_type', NoteType::Project)->first();

    expect($project)->not->toBeNull()
        ->and($a->fresh()->body)->toBe('seed')
        ->and($a->fresh()->title)->toBe('Claim A');

    $page->assertPathBeginsWith('/notes/'.$project->slug);
});
