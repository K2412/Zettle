<?php

use App\Ai\Agents\AtomizeAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Providers\AssistsFakeServiceProvider;
use App\Services\Note\Assists\AtomizeAssist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\AiManager;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('detects candidate ideas with titles and a split rationale', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'A note with two ideas']);

    AtomizeAgent::fake([
        [
            'ideas' => [
                ['title' => 'Mental models compress recurring situations', 'rationale' => 'One claim about compression.'],
                ['title' => 'Compression can blind us to black swans', 'rationale' => 'A distinct claim about failure modes.'],
            ],
        ],
    ]);

    $result = app(AtomizeAssist::class)->run($note);

    expect($result['ideas'])->toHaveCount(2)
        ->and($result['ideas'][0]['title'])->toBe('Mental models compress recurring situations')
        ->and($result['ideas'][1]['rationale'])->toBe('A distinct claim about failure modes.');
});

it('spawns one permanent note per accepted idea with empty bodies and provenance to origin', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'The full raw capture',
        'note_type' => NoteType::Fleeting,
    ]);

    $accepted = [
        'Mental models compress recurring situations',
        'Compression can blind us to black swans',
    ];

    $spawned = app(AtomizeAssist::class)->spawnPermanent($origin, $accepted);

    expect($spawned)->toHaveCount(2);

    foreach ($spawned as $note) {
        expect($note->note_type)->toBe(NoteType::Permanent)
            ->and($note->body)->toBe('')
            ->and($note->user_id)->toBe($user->id);
    }

    expect(collect($spawned)->pluck('title')->all())->toBe($accepted);

    // Each spawned note carries a provenance connection back to the origin.
    foreach ($spawned as $note) {
        $connection = Connection::query()
            ->where('source_note_id', $note->id)
            ->where('target_note_id', $origin->id)
            ->where('relationship', Relationship::Provenance)
            ->first();

        expect($connection)->not->toBeNull();
    }

    expect(Connection::query()->where('relationship', Relationship::Provenance)->count())->toBe(2);
});

it('skips blank and whitespace-only titles', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create(['title' => 'Origin']);

    $spawned = app(AtomizeAssist::class)->spawnPermanent($origin, ['Real idea', '', '   ']);

    expect($spawned)->toHaveCount(1)
        ->and($spawned[0]->title)->toBe('Real idea')
        ->and(Note::query()->where('note_type', NoteType::Permanent)->count())->toBe(1)
        ->and(Connection::query()->where('relationship', Relationship::Provenance)->count())->toBe(1);
});

it('leaves the origin note unchanged when spawning', function () {
    $user = User::factory()->create();
    $origin = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'The full raw capture',
        'note_type' => NoteType::Fleeting,
    ]);

    app(AtomizeAssist::class)->spawnPermanent($origin, ['One idea']);

    expect($origin->fresh())
        ->title->toBe('Origin')
        ->body->toBe('The full raw capture')
        ->note_type->toBe(NoteType::Fleeting);
});

describe('fake gating', function () {
    // Reset the AI manager so no fake carries over from app boot, then boot the
    // provider under a chosen environment/key and read the resulting fake state.
    // Network-free: isFaked() only inspects the in-process fake gateway registry.
    function bootAssistsFakeProvider(string $env, ?string $anthropicKey): bool
    {
        // swap() rebinds the singleton AND clears the facade's resolved cache,
        // so no fake gateway carries over from the app-boot provider.
        Ai::swap(new AiManager(app()));

        app()['env'] = $env;
        config()->set('ai.providers.anthropic.key', $anthropicKey);

        (new AssistsFakeServiceProvider(app()))->boot();

        return AtomizeAgent::isFaked();
    }

    it('installs the fake in local with no key', function () {
        expect(bootAssistsFakeProvider('local', null))->toBeTrue();
    });

    it('installs the fake in testing with no key', function () {
        expect(bootAssistsFakeProvider('testing', null))->toBeTrue();
    });

    it('installs no fake when an anthropic key is set', function () {
        expect(bootAssistsFakeProvider('local', 'sk-real-key'))->toBeFalse();
    });

    it('installs no fake outside local or testing even with no key (fails loud)', function () {
        expect(bootAssistsFakeProvider('production', null))->toBeFalse();
        expect(bootAssistsFakeProvider('staging', null))->toBeFalse();
    });
});
