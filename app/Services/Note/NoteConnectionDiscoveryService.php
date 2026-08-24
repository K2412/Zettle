<?php

namespace App\Services\Note;

use App\Models\Note;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Discovery: given a source note, find the user's OTHER notes nearest to it by
 * embedding, ranked by similarity, as suggestions to connect. Excludes the note
 * itself and notes it already links to, so the list is only new ground. Nothing
 * here creates an edge — the caller turns a suggestion into a connection.
 */
class NoteConnectionDiscoveryService
{
    public function __construct(private NoteEmbeddingService $embeddings) {}

    /**
     * @return Collection<int, array{id: int, title: string, slug: string, snippet: string, similarity: float}>
     */
    public function discover(Note $source, int $limit = 10): Collection
    {
        $vector = $this->resolveSourceVector($source);

        if ($vector === null) {
            return collect();
        }

        $candidates = $this->embeddings->knn($vector, $source->user_id, max($limit * 5, 50));

        if ($candidates->isEmpty()) {
            return collect();
        }

        $excludedIds = $this->excludedNoteIds($source);

        $filtered = $candidates
            ->reject(fn (array $row) => $excludedIds->contains($row['note_id']))
            ->take($limit);

        if ($filtered->isEmpty()) {
            return collect();
        }

        $notes = Note::query()
            ->whereIn('id', $filtered->pluck('note_id'))
            ->get()
            ->keyBy('id');

        return $filtered
            ->map(function (array $row) use ($notes) {
                $note = $notes->get($row['note_id']);

                if ($note === null) {
                    return null;
                }

                return [
                    'id' => $note->id,
                    'title' => $note->title,
                    'slug' => $note->slug,
                    'snippet' => Str::limit(strip_tags((string) $note->body), 200),
                    'similarity' => $this->distanceToSimilarity($row['distance']),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Prefer the note's stored embedding; fall back to embedding its text on the
     * fly (e.g. a note not yet embedded) so discovery still works.
     *
     * @return list<float>|null
     */
    private function resolveSourceVector(Note $source): ?array
    {
        $row = DB::selectOne(
            'SELECT vec_to_json(embedding) AS embedding FROM note_embeddings WHERE note_id = ?',
            [$source->id],
        );

        if ($row !== null && isset($row->embedding) && is_string($row->embedding)) {
            $decoded = json_decode($row->embedding, true);

            if (is_array($decoded)) {
                return array_values(array_map('floatval', $decoded));
            }
        }

        return $this->embeddings->generateVector(trim($source->title."\n\n".(string) $source->body));
    }

    /**
     * The source note and everything it already connects to, in either
     * direction — discovery only surfaces new ground.
     *
     * @return Collection<int, int>
     */
    private function excludedNoteIds(Note $source): Collection
    {
        $source->loadMissing(['linksTo:id', 'linkedFrom:id']);

        return collect([$source->id])
            ->merge($source->linksTo->pluck('id'))
            ->merge($source->linkedFrom->pluck('id'))
            ->unique()
            ->values();
    }

    private function distanceToSimilarity(float $distance): float
    {
        return round(max(0.0, 1.0 - ($distance / 2.0)), 4);
    }
}
