<?php

namespace App\Support\Search;

/**
 * Reciprocal Rank Fusion: merges several ranked id lists into one, scoring each
 * id by the sum of 1/(k + rank) across the lists it appears in (rank starts at
 * 1). An id ranked well in either list rises; ties break by first appearance so
 * fusion is deterministic. This is how keyword and vector search are blended.
 */
class ReciprocalRankFusion
{
    /**
     * @param  list<list<int|string>>  $rankings
     * @return list<int|string>
     */
    public static function fuse(array $rankings, int $k = 60): array
    {
        /** @var array<int|string, float> $scores */
        $scores = [];
        /** @var array<int|string, int> $firstSeen */
        $firstSeen = [];
        $order = 0;

        foreach ($rankings as $ranking) {
            foreach (array_values($ranking) as $index => $id) {
                $rank = $index + 1;
                $scores[$id] = ($scores[$id] ?? 0.0) + (1 / ($k + $rank));

                if (! array_key_exists($id, $firstSeen)) {
                    $firstSeen[$id] = $order++;
                }
            }
        }

        $ids = array_keys($scores);

        usort($ids, function ($a, $b) use ($scores, $firstSeen) {
            return [$scores[$b], $firstSeen[$a]] <=> [$scores[$a], $firstSeen[$b]];
        });

        return array_values($ids);
    }
}
