<?php

use Laravel\Ai\Embeddings;
use Tests\Support\FakeEmbeddings;

it('resolves the embeddings SDK and returns a faked deterministic vector', function () {
    $fake = FakeEmbeddings::install();
    $fake->map('hello world', [1.0, 0.0, 0.0]);

    $response = Embeddings::for(['hello world'])
        ->dimensions((int) config('ai.zettle.embedding.dimensions'))
        ->generate(model: (string) config('ai.zettle.embedding.model'));

    $vector = $response->embeddings[0];

    expect($vector)->toBeArray()
        ->and($vector)->toHaveCount((int) config('ai.zettle.embedding.dimensions'))
        ->and($vector[0])->toBe(1.0);
});

it('configures openai as the embeddings provider with the expected model and dimensions', function () {
    expect(config('ai.default_for_embeddings'))->toBe('openai')
        ->and(config('ai.providers.openai.driver'))->toBe('openai')
        ->and(config('ai.zettle.embedding.model'))->toBe('text-embedding-3-small')
        ->and(config('ai.zettle.embedding.dimensions'))->toBe(1536);
});

it('reads the openai key from config, not env() in code', function () {
    // The key travels through config/ai.php; code must never call env() directly.
    expect(config('ai.providers.openai'))->toHaveKey('key');
});
