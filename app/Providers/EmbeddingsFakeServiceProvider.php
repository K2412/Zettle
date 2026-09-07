<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

/**
 * Without a real OpenAI key the app can't call the embeddings API, so outside
 * production this provider installs the AI SDK's embeddings fake with stable,
 * text-derived vectors. That keeps local dev and browser tests — which run in a
 * separate server process the in-test FakeEmbeddings helper can't reach —
 * network-free and deterministic. A configured key leaves the real API in place.
 */
class EmbeddingsFakeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Allowlist, not denylist: only local dev and the test suite ever get the
        // fake. A keyless staging/preview then fails loud on a real API call
        // instead of silently serving meaningless text-hashed vectors.
        if (! $this->app->environment('local', 'testing')) {
            return;
        }

        if (! empty(config('ai.providers.openai.key'))) {
            return;
        }

        Embeddings::fake(fn (EmbeddingsPrompt $prompt) => array_map(
            fn (string $input) => $this->deterministicVector($input, $prompt->dimensions),
            $prompt->inputs,
        ));
    }

    /**
     * A stable unit vector derived from the text, so the same note or query
     * always embeds to the same point run to run.
     *
     * @return list<float>
     */
    private function deterministicVector(string $text, int $dimensions): array
    {
        $vector = [];

        for ($i = 0; $i < $dimensions; $i++) {
            $byte = hexdec(substr(hash('sha256', trim($text).':'.$i), 0, 8));
            $vector[] = ($byte / 0xFFFFFFFF) - 0.5;
        }

        $magnitude = sqrt(array_sum(array_map(fn (float $v) => $v * $v, $vector)));

        return $magnitude === 0.0
            ? $vector
            : array_map(fn (float $v) => $v / $magnitude, $vector);
    }
}
