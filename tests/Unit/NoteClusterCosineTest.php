<?php

use App\Services\Note\NoteClusterService;

// Expected values are worked out by hand, independent of the implementation.
dataset('cosine cases', [
    'identical vectors → 1' => [[1.0, 0.0], [1.0, 0.0], 1.0],
    'orthogonal vectors → 0' => [[1.0, 0.0], [0.0, 1.0], 0.0],
    'opposite vectors → -1' => [[1.0, 0.0], [-1.0, 0.0], -1.0],
    '45 degrees → 1/sqrt(2)' => [[1.0, 1.0], [1.0, 0.0], 0.70710678118],
    'parallel, different magnitude' => [[3.0, 4.0], [6.0, 8.0], 1.0],
    'zero vector guarded → 0' => [[0.0, 0.0], [1.0, 0.0], 0.0],
    'compares over shorter length' => [[1.0, 0.0, 99.0], [1.0, 0.0], 1.0],
]);

it('computes cosine similarity for known vectors', function (array $a, array $b, float $expected) {
    expect(NoteClusterService::cosine($a, $b))->toEqualWithDelta($expected, 1e-9);
})->with('cosine cases');

it('is symmetric', function () {
    $a = [0.2, 0.9, -0.4];
    $b = [0.7, -0.1, 0.5];

    expect(NoteClusterService::cosine($a, $b))
        ->toEqualWithDelta(NoteClusterService::cosine($b, $a), 1e-9);
});
