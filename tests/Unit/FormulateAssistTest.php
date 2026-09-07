<?php

use App\Ai\Agents\AtomizeAgent;
use App\Ai\Agents\FormulateAgent;
use App\Ai\Agents\TriageAgent;
use App\Models\Note;
use App\Models\User;
use App\Providers\AssistsFakeServiceProvider;
use App\Services\Note\Assists\FormulateAssist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\AiManager;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('evaluates a draft and returns the prose critique', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'A claim']);

    FormulateAgent::fake([
        'Your title states a topic, not a claim. Consider: "X causes Y because Z."',
    ]);

    $critique = app(FormulateAssist::class)->evaluate($note, 'A rough draft the user typed');

    expect($critique)->toBeString()
        ->and($critique)->toContain('states a topic, not a claim');
});

it('never modifies the note when evaluating', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'A claim',
        'body' => 'The original stored body',
    ]);

    FormulateAgent::fake(['Some critique prose.']);

    app(FormulateAssist::class)->evaluate($note, 'A rough draft the user typed');

    expect($note->fresh())
        ->title->toBe('A claim')
        ->body->toBe('The original stored body');
});

describe('fake gating', function () {
    // Reset the AI manager so no fake carries over from app boot, then boot the
    // provider under a chosen environment/key and read the resulting fake state.
    // Network-free: isFaked() only inspects the in-process fake gateway registry.
    function bootAssistsFakeProviderForFormulate(string $env, ?string $anthropicKey): bool
    {
        // swap() rebinds the singleton AND clears the facade's resolved cache,
        // so no fake gateway carries over from the app-boot provider.
        Ai::swap(new AiManager(app()));

        app()['env'] = $env;
        config()->set('ai.providers.anthropic.key', $anthropicKey);

        (new AssistsFakeServiceProvider(app()))->boot();

        return AtomizeAgent::isFaked() && TriageAgent::isFaked() && FormulateAgent::isFaked();
    }

    it('fakes all three assist agents in local with no key', function () {
        expect(bootAssistsFakeProviderForFormulate('local', null))->toBeTrue();
    });

    it('installs no fake when an anthropic key is set', function () {
        expect(bootAssistsFakeProviderForFormulate('local', 'sk-real-key'))->toBeFalse();
    });

    it('installs no fake outside local or testing even with no key (fails loud)', function () {
        expect(bootAssistsFakeProviderForFormulate('production', null))->toBeFalse();
        expect(bootAssistsFakeProviderForFormulate('staging', null))->toBeFalse();
    });
});
