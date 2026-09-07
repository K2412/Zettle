<?php

use App\Support\Search\ReciprocalRankFusion;

it('ranks an id that tops both lists first', function () {
    // Id 7 is rank 1 in keyword AND rank 1 in vector — strictly the best score,
    // beating id 3 which is only rank 2 / rank 2.
    $keyword = [7, 3, 9];
    $vector = [7, 3, 5];

    $fused = ReciprocalRankFusion::fuse([$keyword, $vector]);

    expect($fused[0])->toBe(7);
});

it('unions ids across both lists, keeping vector-only matches', function () {
    $keyword = [1, 2];
    $vector = [2, 8];

    $fused = ReciprocalRankFusion::fuse([$keyword, $vector]);

    expect($fused)->toContain(8)
        ->and($fused)->toContain(1)
        ->and(count($fused))->toBe(3);
});

it('computes RRF scores deterministically with the given k', function () {
    // With k=60: id A at ranks 1 and 1 → 1/61 + 1/61; id B at rank 2 and absent → 1/62.
    $listOne = ['A', 'B'];
    $listTwo = ['A', 'C'];

    $fused = ReciprocalRankFusion::fuse([$listOne, $listTwo], k: 60);

    // A dominates (in both at top); B (1/62) beats C (1/62) only by insertion
    // order tie-break, so assert A first and both B and C present after.
    expect($fused[0])->toBe('A')
        ->and(array_slice($fused, 1))->toEqualCanonicalizing(['B', 'C']);
});

it('breaks ties by first appearance, stably', function () {
    // B and C each appear once at rank 2 → equal score; B seen first stays ahead.
    $listOne = ['A', 'B'];
    $listTwo = ['A', 'C'];

    $fused = ReciprocalRankFusion::fuse([$listOne, $listTwo]);

    expect(array_search('B', $fused))->toBeLessThan(array_search('C', $fused));
});

it('returns an empty list when every input list is empty', function () {
    expect(ReciprocalRankFusion::fuse([[], []]))->toBe([]);
});

it('handles a single non-empty list as a passthrough of its order', function () {
    expect(ReciprocalRankFusion::fuse([[5, 4, 6], []]))->toBe([5, 4, 6]);
});
