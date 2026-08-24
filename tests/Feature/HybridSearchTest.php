<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\Note\NoteEmbeddingService;
use App\Services\Note\NoteService;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Embeddings;
use Tests\Support\FakeEmbeddings;

function embedAll(iterable $notes): void
{
    $service = app(NoteEmbeddingService::class);
    foreach ($notes as $note) {
        $service->embed($note);
    }
}

it('surfaces a semantic match that keyword search alone would miss', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();

    // "canine" shares no keyword with "dog" but should be embedding-near.
    $keywordHit = Note::factory()->for($user)->create(['title' => 'All about dogs', 'body' => 'dog dog']);
    $semanticHit = Note::factory()->for($user)->create(['title' => 'Canine companions', 'body' => 'loyal pets']);
    $unrelated = Note::factory()->for($user)->create(['title' => 'Tax filing', 'body' => 'forms']);

    $fake->map('dog', [1.0, 0.0])
        ->map('Canine companions'."\n\n".'loyal pets', [0.98, 0.02])
        ->map('All about dogs'."\n\n".'dog dog', [0.6, 0.4])
        ->map('Tax filing'."\n\n".'forms', [0.0, 1.0]);

    embedAll([$keywordHit, $semanticHit, $unrelated]);

    $results = app(NoteService::class)->listForUser($user, 'dog');
    $ids = collect($results->items())->pluck('id')->all();

    // Both the keyword hit and the embedding-only "canine" note surface, and
    // both outrank the unrelated note in the fused order.
    expect($ids)->toContain($semanticHit->id)
        ->and($ids)->toContain($keywordHit->id)
        ->and(array_search($semanticHit->id, $ids))->toBeLessThan(array_search($unrelated->id, $ids))
        ->and(array_search($keywordHit->id, $ids))->toBeLessThan(array_search($unrelated->id, $ids));
});

it('embeds nothing and returns the latest list for an empty query', function () {
    FakeEmbeddings::install();
    $user = User::factory()->create();
    Note::factory()->for($user)->count(3)->create();

    $results = app(NoteService::class)->listForUser($user, '');

    expect($results->total())->toBe(3);
    Embeddings::assertNothingGenerated();
});

it('keeps hybrid results scoped to the user', function () {
    $fake = FakeEmbeddings::install();
    $mine = User::factory()->create();
    $theirs = User::factory()->create();

    $mineNote = Note::factory()->for($mine)->create(['title' => 'Mine dog', 'body' => '']);
    $theirNote = Note::factory()->for($theirs)->create(['title' => 'Their dog', 'body' => '']);

    $fake->map('dog', [1.0, 0.0])
        ->map('Mine dog', [1.0, 0.0])
        ->map('Their dog', [1.0, 0.0]);

    embedAll([$mineNote, $theirNote]);

    $ids = collect(app(NoteService::class)->listForUser($mine, 'dog')->items())->pluck('id');

    expect($ids)->toContain($mineNote->id)
        ->and($ids)->not->toContain($theirNote->id);
});

it('renders the hybrid-ranked notes as the index prop for a query', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();

    $hit = Note::factory()->for($user)->create(['title' => 'Neural networks', 'body' => '']);
    $fake->map('deep learning', [1.0, 0.0])->map('Neural networks', [0.97, 0.03]);
    embedAll([$hit]);

    $this->actingAs($user)
        ->get(route('notes.index', ['q' => 'deep learning']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/index')
            ->where('filters.q', 'deep learning')
            ->has('notes.data')
        );
});

it('scopes a hybrid search to a tag before fusing, keeping the total correct', function () {
    $fake = FakeEmbeddings::install();
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();

    // Both notes are embedding-near the query, but only one carries the tag.
    $tagged = Note::factory()->for($user)->create(['title' => 'Canine companions', 'body' => 'loyal pets']);
    $untagged = Note::factory()->for($user)->create(['title' => 'Wolves', 'body' => 'wild canines']);
    $tagged->tags()->attach($tag);

    $fake->map('dog', [1.0, 0.0])
        ->map('Canine companions'."\n\n".'loyal pets', [0.98, 0.02])
        ->map('Wolves'."\n\n".'wild canines', [0.97, 0.03]);

    embedAll([$tagged, $untagged]);

    $results = app(NoteService::class)->listForUser($user, 'dog', $tag->id);
    $ids = collect($results->items())->pluck('id')->all();

    // The untagged semantic match is excluded before fusion, and the total
    // reflects only the tagged result — not the pre-filter candidate count.
    expect($ids)->toContain($tagged->id)
        ->and($ids)->not->toContain($untagged->id)
        ->and($results->total())->toBe(1);
});
