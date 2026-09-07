<?php

use App\Ai\Agents\ConnectAgent;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\Assists\ConnectAssist;
use App\Services\Note\NoteConnectionDiscoveryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

/**
 * Bind a fake discovery service that returns the given canned neighbours,
 * so the assist can be tested without the sqlite-vec extension.
 *
 * @param  list<array<string, mixed>>  $neighbours
 */
function fakeDiscovery(array $neighbours): void
{
    $fake = new class($neighbours) extends NoteConnectionDiscoveryService
    {
        public function __construct(private array $canned) {}

        public function discover(Note $source, int $limit = 10): Collection
        {
            return collect($this->canned);
        }
    };

    app()->instance(NoteConnectionDiscoveryService::class, $fake);
}

it('returns unlinked neighbours each with a relationship and rationale', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create(['title' => 'Source claim']);
    $neighbour = Note::factory()->for($user)->create(['title' => 'Neighbour claim']);

    fakeDiscovery([
        ['id' => $neighbour->id, 'title' => 'Neighbour claim', 'slug' => $neighbour->slug, 'snippet' => 'a snippet', 'similarity' => 0.9],
    ]);

    ConnectAgent::fake([
        [
            'suggestions' => [
                ['note_id' => $neighbour->id, 'relationship' => 'qualifies', 'rationale' => 'Qualifies the source because scope is narrower.'],
            ],
        ],
    ]);

    $candidates = app(ConnectAssist::class)->run($source);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]['note_id'])->toBe($neighbour->id)
        ->and($candidates[0]['title'])->toBe('Neighbour claim')
        ->and($candidates[0]['relationship'])->toBe(Relationship::Qualifies)
        ->and($candidates[0]['rationale'])->toBe('Qualifies the source because scope is narrower.');
});

it('creates exactly one typed connection with the accepted relationship and rationale', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $target = Note::factory()->for($user)->create();

    app(ConnectAssist::class)->link(
        $source,
        $target,
        Relationship::Extends,
        'Extends the target by adding a further case.',
    );

    $connections = Connection::query()
        ->where('source_note_id', $source->id)
        ->where('target_note_id', $target->id)
        ->get();

    expect($connections)->toHaveCount(1)
        ->and($connections->first()->relationship)->toBe(Relationship::Extends)
        ->and($connections->first()->rationale)->toBe('Extends the target by adding a further case.');
});

it('pairs a canned note_id 0 suggestion with the first unmatched neighbour', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create(['title' => 'Source claim']);
    $neighbour = Note::factory()->for($user)->create(['title' => 'Neighbour claim']);

    fakeDiscovery([
        ['id' => $neighbour->id, 'title' => 'Neighbour claim', 'slug' => $neighbour->slug, 'snippet' => 'a snippet', 'similarity' => 0.9],
    ]);

    ConnectAgent::fake([
        [
            'suggestions' => [
                ['note_id' => 0, 'relationship' => 'supports', 'rationale' => 'Supports the neighbour by sharing the same claim.'],
            ],
        ],
    ]);

    $candidates = app(ConnectAssist::class)->run($source);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]['note_id'])->toBe($neighbour->id)
        ->and($candidates[0]['relationship'])->toBe(Relationship::Supports)
        ->and($candidates[0]['rationale'])->toBe('Supports the neighbour by sharing the same claim.');
});
