<?php

use App\Ai\Agents\AtomizeAgent;
use App\Ai\Agents\TriageAgent;
use App\Enums\NoteType;
use App\Enums\TriageDestination;
use App\Models\Note;
use App\Models\User;
use App\Providers\AssistsFakeServiceProvider;
use App\Services\Note\Assists\TriageAssist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\AiManager;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('has the seven triage destinations with their expected string values', function () {
    expect(collect(TriageDestination::cases())->pluck('value')->all())->toBe([
        'discard',
        'task',
        'project_only',
        'keep_literature',
        'develop',
        'question',
        'multi_idea',
    ]);
});

it('classifies a destination, suggests a note type, and returns reasoning', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'A raw capture']);

    TriageAgent::fake([
        [
            'destination' => 'develop',
            'note_type' => 'permanent',
            'reasoning' => 'It extends an idea you hold.',
        ],
    ]);

    $result = app(TriageAssist::class)->run($note);

    expect($result['destination'])->toBe(TriageDestination::Develop)
        ->and($result['note_type'])->toBe(NoteType::Permanent)
        ->and($result['reasoning'])->toBe('It extends an idea you hold.');
});

it('applies a note type and leaves title and body byte-for-byte unchanged', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Origin',
        'body' => 'The full raw capture',
        'note_type' => NoteType::Fleeting,
    ]);

    app(TriageAssist::class)->applyType($note, NoteType::Permanent);

    expect($note->fresh())
        ->note_type->toBe(NoteType::Permanent)
        ->title->toBe('Origin')
        ->body->toBe('The full raw capture');
});

describe('fake gating', function () {
    // Reset the AI manager so no fake carries over from app boot, then boot the
    // provider under a chosen environment/key and read the resulting fake state.
    // Network-free: isFaked() only inspects the in-process fake gateway registry.
    function bootAssistsFakeProviderForTriage(string $env, ?string $anthropicKey): bool
    {
        // swap() rebinds the singleton AND clears the facade's resolved cache,
        // so no fake gateway carries over from the app-boot provider.
        Ai::swap(new AiManager(app()));

        app()['env'] = $env;
        config()->set('ai.providers.anthropic.key', $anthropicKey);

        (new AssistsFakeServiceProvider(app()))->boot();

        return AtomizeAgent::isFaked() && TriageAgent::isFaked();
    }

    it('installs fakes for every assist agent in local with no key', function () {
        expect(bootAssistsFakeProviderForTriage('local', null))->toBeTrue();
    });

    it('installs fakes for every assist agent in testing with no key', function () {
        expect(bootAssistsFakeProviderForTriage('testing', null))->toBeTrue();
    });

    it('installs no fake when an anthropic key is set', function () {
        expect(bootAssistsFakeProviderForTriage('local', 'sk-real-key'))->toBeFalse();
    });

    it('installs no fake outside local or testing even with no key (fails loud)', function () {
        expect(bootAssistsFakeProviderForTriage('production', null))->toBeFalse();
        expect(bootAssistsFakeProviderForTriage('staging', null))->toBeFalse();
    });
});
