<?php

namespace Tests\Support;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

/**
 * Deterministic embedding fake for the test suite. Installs the AI SDK's
 * embeddings fake so no test hits the network, and maps input text to a stable
 * unit vector: tests register exact text→vector pairs to control which notes
 * land near a query, and any unregistered text hashes to its own deterministic
 * vector so embeddings are always reproducible run to run.
 */
class FakeEmbeddings
{
    /** @var array<string, list<float>> */
    private array $vectors = [];

    private function __construct(private int $dimensions) {}

    public static function install(?int $dimensions = null): self
    {
        $dimensions ??= (int) config('ai.zettle.embedding.dimensions');

        $fake = new self($dimensions);

        Embeddings::fake(fn (EmbeddingsPrompt $prompt) => array_map(
            fn (string $input) => $fake->vectorFor($input),
            $prompt->inputs,
        ));

        return $fake;
    }

    /**
     * Register the exact vector a given text should embed to. The vector is
     * padded/truncated to the configured dimension count and normalised.
     *
     * @param  list<float>  $vector
     */
    public function map(string $text, array $vector): self
    {
        $this->vectors[trim($text)] = $this->normalise($this->fit($vector));

        return $this;
    }

    /**
     * @return list<float>
     */
    public function vectorFor(string $text): array
    {
        $text = trim($text);

        return $this->vectors[$text] ?? $this->hashVector($text);
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    private function fit(array $vector): array
    {
        $vector = array_slice($vector, 0, $this->dimensions);

        return array_pad($vector, $this->dimensions, 0.0);
    }

    /**
     * A stable, roughly-uniform unit vector derived from the text, so unmapped
     * inputs stay far from mapped ones without any coordination.
     *
     * @return list<float>
     */
    private function hashVector(string $text): array
    {
        $vector = [];

        for ($i = 0; $i < $this->dimensions; $i++) {
            $byte = hexdec(substr(hash('sha256', $text.':'.$i), 0, 8));
            $vector[] = ($byte / 0xFFFFFFFF) - 0.5;
        }

        return $this->normalise($vector);
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    private function normalise(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(fn (float $v) => $v * $v, $vector)));

        if ($magnitude === 0.0) {
            return $vector;
        }

        return array_map(fn (float $v) => $v / $magnitude, $vector);
    }
}
