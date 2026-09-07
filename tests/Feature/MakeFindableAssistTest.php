<?php

use App\Ai\Agents\MakeFindableAgent;
use App\Ai\Agents\NoteTagSuggester;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\Note\Assists\MakeFindableAssist;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('returns retrieval contexts, a discovery hint and tag suggestions', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create([
        'title' => 'Spaced repetition beats cramming',
        'body' => 'Distributing practice over time yields better retention.',
    ]);

    MakeFindableAgent::fake([
        [
            'retrieval_contexts' => [
                'How do I schedule study sessions for retention?',
                'Why does cramming fail before an exam?',
            ],
            'discovery_hint' => 'Reach for this when planning a study schedule.',
        ],
    ]);

    NoteTagSuggester::fake([
        ['tags' => ['spaced-repetition', 'memory', 'learning']],
    ]);

    $result = app(MakeFindableAssist::class)->run($note);

    expect($result['retrieval_contexts'])->toBe([
        'How do I schedule study sessions for retention?',
        'Why does cramming fail before an exam?',
    ])
        ->and($result['discovery_hint'])->toBe('Reach for this when planning a study schedule.')
        ->and($result['tags'])->toBe(['spaced-repetition', 'memory', 'learning']);

    // ADR-0003: the note content is never touched by run().
    expect($note->fresh())
        ->title->toBe('Spaced repetition beats cramming')
        ->body->toBe('Distributing practice over time yields better retention.');
});

it('attaches a tag as an explicit user action without touching note content', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'T', 'body' => 'B']);

    $tag = app(MakeFindableAssist::class)->attachTag($note, 'memory');

    expect($tag)->toBeInstanceOf(Tag::class)
        ->and($note->fresh()->tags->pluck('name')->all())->toContain('memory')
        ->and($note->fresh())->title->toBe('T')->body->toBe('B');
});

it('sets the discovery hint without touching note title or body', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'T', 'body' => 'B']);

    app(MakeFindableAssist::class)->setDiscoveryHint($note, 'Reach for this when X.');

    expect($note->fresh())
        ->discovery_hint->toBe('Reach for this when X.')
        ->title->toBe('T')
        ->body->toBe('B');
});
