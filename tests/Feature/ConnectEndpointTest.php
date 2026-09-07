<?php

use App\Ai\Agents\ConnectAgent;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteConnectionDiscoveryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Inertia\Support\SessionKey;

beforeEach(fn () => Queue::fake());

/**
 * @param  list<array<string, mixed>>  $neighbours
 */
function fakeConnectDiscovery(array $neighbours): void
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

it('returns typed-connection candidates as JSON for the owner', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create();
    $neighbour = Note::factory()->for($user)->create(['title' => 'Neighbour']);

    fakeConnectDiscovery([
        [
            'id' => $neighbour->id,
            'title' => 'Neighbour',
            'slug' => $neighbour->slug,
            'snippet' => 'a snippet',
            'similarity' => 0.9,
        ],
    ]);

    ConnectAgent::fake([
        [
            'suggestions' => [
                [
                    'note_id' => $neighbour->id,
                    'relationship' => 'qualifies',
                    'rationale' => 'Narrower in scope.',
                ],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->postJson(route('notes.assists.connect', $source))
        ->assertOk()
        ->assertJsonPath('candidates.0.note_id', $neighbour->id)
        ->assertJsonPath('candidates.0.relationship', 'qualifies')
        ->assertJsonPath('candidates.0.rationale', 'Narrower in scope.');
});

it('forbids running connect on a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->postJson(route('notes.assists.connect', $note))
        ->assertForbidden();
});

it('creates a typed connection, redirects back, and flashes a success toast', function () {
    $user = User::factory()->create();
    $source = Note::factory()->for($user)->create(['title' => 'Origin', 'body' => 'raw']);
    $target = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('notes.show', $source))
        ->post(route('notes.assists.connect.link', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Supports->value,
            'rationale' => 'Shares the claim.',
        ])
        ->assertRedirect(route('notes.show', $source))
        ->assertSessionHas(SessionKey::FLASH_DATA, [
            'toast' => ['type' => 'success', 'message' => 'Connection created.'],
        ]);

    expect(Connection::query()
        ->where('source_note_id', $source->id)
        ->where('target_note_id', $target->id)
        ->where('relationship', Relationship::Supports)
        ->exists())->toBeTrue()
        ->and($source->fresh())->title->toBe('Origin')->body->toBe('raw');
});

it('forbids linking from a note owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $source = Note::factory()->for($owner)->create();
    $target = Note::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->post(route('notes.assists.connect.link', $source), [
            'target_note_id' => $target->id,
            'relationship' => Relationship::Supports->value,
        ])
        ->assertForbidden();
});
