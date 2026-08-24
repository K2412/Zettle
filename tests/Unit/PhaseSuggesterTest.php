<?php

use App\Enums\NoteType;
use App\Enums\Phase;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\PhaseSuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function suggestPhase(Note $note): Phase
{
    return app(PhaseSuggester::class)->suggest($note);
}

it('suggests Triage for a fleeting note', function () {
    $note = Note::factory()->create(['note_type' => NoteType::Fleeting]);

    expect(suggestPhase($note))->toBe(Phase::Triage);
});

it('suggests Triage for a literature note', function () {
    $note = Note::factory()->create(['note_type' => NoteType::Literature]);

    expect(suggestPhase($note))->toBe(Phase::Triage);
});

it('suggests Cluster to project for a structure note', function () {
    $note = Note::factory()->create(['note_type' => NoteType::Structure]);

    expect(suggestPhase($note))->toBe(Phase::ClusterProject);
});

it('suggests Formulate for a permanent note with no body', function () {
    $note = Note::factory()->create([
        'note_type' => NoteType::Permanent,
        'body' => '',
    ]);

    expect(suggestPhase($note))->toBe(Phase::Formulate);
});

it('suggests Connect for a permanent note with a body but no connections', function () {
    $note = Note::factory()->create([
        'note_type' => NoteType::Permanent,
        'body' => 'A written claim.',
    ]);

    expect(suggestPhase($note))->toBe(Phase::Connect);
});

it('suggests Structure for a permanent note with a body and a connection', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'note_type' => NoteType::Permanent,
        'body' => 'A written claim.',
    ]);
    $other = Note::factory()->for($user)->create();

    Connection::query()->create([
        'user_id' => $user->id,
        'source_note_id' => $note->id,
        'target_note_id' => $other->id,
        'relationship' => Relationship::Supports,
    ]);

    expect(suggestPhase($note))->toBe(Phase::Structure);
});

it('treats an incoming connection as connected too', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'note_type' => NoteType::Permanent,
        'body' => 'A written claim.',
    ]);
    $other = Note::factory()->for($user)->create();

    Connection::query()->create([
        'user_id' => $user->id,
        'source_note_id' => $other->id,
        'target_note_id' => $note->id,
        'relationship' => Relationship::Supports,
    ]);

    expect(suggestPhase($note))->toBe(Phase::Structure);
});
