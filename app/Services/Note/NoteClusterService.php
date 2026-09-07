<?php

namespace App\Services\Note;

use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detects clusters in a user's note graph as connected components over the
 * `connections` edges (BFS), scored for project-readiness by
 * size × internal link density × mean pairwise sqlite-vec cohesion.
 *
 * Relational only — graphqlite is deliberately deferred (ADR-0002).
 */
class NoteClusterService
{
    /**
     * @param  (Closure(list<int>): float)|null  $cohesion  Override the vec cohesion
     *                                                      computation (used in tests to stay independent of sqlite-vec).
     */
    public function __construct(
        private ?Closure $cohesion = null,
    ) {}

    /**
     * Return every connected component of the user's note graph, each scored.
     *
     * @return Collection<int, array{note_ids: list<int>, size: int, density: float, cohesion: float, score: float, ripe: bool}>
     */
    public function components(User $user): Collection
    {
        $noteIds = Note::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $adjacency = $this->buildAdjacency($user, $noteIds);

        $visited = [];
        $components = collect();

        foreach ($noteIds as $start) {
            if (isset($visited[$start])) {
                continue;
            }

            $componentIds = $this->bfs($start, $adjacency, $visited);

            $components->push($this->describe($componentIds, $adjacency));
        }

        return $components->values();
    }

    /**
     * Components that clear the ripeness thresholds, richest first.
     *
     * @return Collection<int, array{note_ids: list<int>, size: int, density: float, cohesion: float, score: float, ripe: bool}>
     */
    public function ripeClusters(User $user): Collection
    {
        return $this->components($user)
            ->filter(fn (array $c) => $c['ripe'])
            ->sortByDesc('score')
            ->values();
    }

    /**
     * Build an undirected adjacency map from the connections table.
     *
     * @param  list<int>  $noteIds
     * @return array<int, array<int, true>>
     */
    private function buildAdjacency(User $user, array $noteIds): array
    {
        $adjacency = [];
        foreach ($noteIds as $id) {
            $adjacency[$id] = [];
        }

        $edges = Connection::query()
            ->where('user_id', $user->id)
            ->get(['source_note_id', 'target_note_id']);

        foreach ($edges as $edge) {
            $s = (int) $edge->source_note_id;
            $t = (int) $edge->target_note_id;

            if ($s === $t || ! isset($adjacency[$s]) || ! isset($adjacency[$t])) {
                continue;
            }

            $adjacency[$s][$t] = true;
            $adjacency[$t][$s] = true;
        }

        return $adjacency;
    }

    /**
     * @param  array<int, array<int, true>>  $adjacency
     * @param  array<int, true>  $visited
     * @return list<int>
     */
    private function bfs(int $start, array $adjacency, array &$visited): array
    {
        $queue = [$start];
        $visited[$start] = true;
        $component = [];

        while ($queue !== []) {
            $node = array_shift($queue);
            $component[] = $node;

            foreach (array_keys($adjacency[$node] ?? []) as $neighbour) {
                if (! isset($visited[$neighbour])) {
                    $visited[$neighbour] = true;
                    $queue[] = $neighbour;
                }
            }
        }

        return $component;
    }

    /**
     * @param  list<int>  $componentIds
     * @param  array<int, array<int, true>>  $adjacency
     * @return array{note_ids: list<int>, size: int, density: float, cohesion: float, score: float, ripe: bool}
     */
    private function describe(array $componentIds, array $adjacency): array
    {
        $size = count($componentIds);

        // Undirected edge count within the component.
        $ids = array_flip($componentIds);
        $edgeCount = 0;
        foreach ($componentIds as $node) {
            foreach (array_keys($adjacency[$node] ?? []) as $neighbour) {
                if (isset($ids[$neighbour]) && $node < $neighbour) {
                    $edgeCount++;
                }
            }
        }

        // Density = actual edges / possible edges (a single node has density 0).
        $possible = $size > 1 ? ($size * ($size - 1)) / 2 : 0;
        $density = $possible > 0 ? $edgeCount / $possible : 0.0;

        $cohesion = $this->meanPairwiseCohesion($componentIds);

        // size × density × cohesion. A single, isolated node scores 0.
        $score = $size * $density * $cohesion;

        $ripe = $size >= (int) config('ai.zettle.cluster.min_size', 3)
            && $score >= (float) config('ai.zettle.cluster.ripe_score', 1.5);

        return [
            'note_ids' => array_values($componentIds),
            'size' => $size,
            'density' => round($density, 4),
            'cohesion' => round($cohesion, 4),
            'score' => round($score, 4),
            'ripe' => $ripe,
        ];
    }

    /**
     * @param  list<int>  $noteIds
     */
    private function meanPairwiseCohesion(array $noteIds): float
    {
        if ($this->cohesion !== null) {
            return ($this->cohesion)($noteIds);
        }

        if (count($noteIds) < 2) {
            return 1.0;
        }

        return $this->vecCohesion($noteIds);
    }

    /**
     * Mean pairwise cosine similarity across the component's note embeddings,
     * using the sqlite-vec store. Falls back to a neutral 1.0 if embeddings
     * are unavailable (e.g. the extension is not loaded).
     *
     * @param  list<int>  $noteIds
     */
    private function vecCohesion(array $noteIds): float
    {
        try {
            $placeholders = implode(',', array_fill(0, count($noteIds), '?'));

            $rows = DB::select(
                "SELECT note_id, vec_to_json(embedding) AS embedding FROM note_embeddings WHERE note_id IN ($placeholders)",
                $noteIds,
            );
        } catch (\Throwable) {
            return 1.0;
        }

        $vectors = [];
        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->embedding, true);
            if (is_array($decoded)) {
                $vectors[] = array_map('floatval', $decoded);
            }
        }

        if (count($vectors) < 2) {
            return 1.0;
        }

        $sum = 0.0;
        $pairs = 0;
        for ($i = 0; $i < count($vectors); $i++) {
            for ($j = $i + 1; $j < count($vectors); $j++) {
                $sum += self::cosine($vectors[$i], $vectors[$j]);
                $pairs++;
            }
        }

        return $pairs > 0 ? $sum / $pairs : 1.0;
    }

    /**
     * Cosine similarity between two vectors. Pure and sqlite-vec-independent so
     * the scoring math can be unit-tested directly. Returns 0.0 for a zero vector
     * and compares over the shorter length when dimensions differ.
     *
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $len = min(count($a), count($b));

        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] ** 2;
            $nb += $b[$i] ** 2;
        }

        if ($na == 0.0 || $nb == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
