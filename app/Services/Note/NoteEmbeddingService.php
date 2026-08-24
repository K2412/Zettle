<?php

namespace App\Services\Note;

use App\Models\Note;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;

/**
 * Generates note embeddings via the AI SDK and stores them in the sqlite-vec
 * note_embeddings table. embed/forget keep a note's vector in sync; knn runs the
 * nearest-neighbour query semantic search and discovery both build on.
 */
class NoteEmbeddingService
{
    public function embed(Note $note): void
    {
        $text = trim($note->title."\n\n".(string) $note->body);

        if ($text === '') {
            $this->forget($note->id);

            return;
        }

        $dimensions = (int) config('ai.zettle.embedding.dimensions');
        $vector = $this->generateVector($text);

        if ($vector === null || count($vector) !== $dimensions) {
            return;
        }

        DB::statement(
            'INSERT OR REPLACE INTO note_embeddings(note_id, user_id, embedding) VALUES (?, ?, vec_f32(?))',
            [$note->id, $note->user_id, json_encode($vector)],
        );
    }

    public function forget(int $noteId): void
    {
        DB::statement('DELETE FROM note_embeddings WHERE note_id = ?', [$noteId]);
    }

    /**
     * @param  list<float>  $queryVector
     * @return Collection<int, array{note_id: int, distance: float}>
     */
    public function knn(array $queryVector, int $userId, int $k = 50): Collection
    {
        $rows = DB::select(
            'SELECT note_id, distance FROM note_embeddings WHERE embedding MATCH ? AND user_id = ? AND k = ? ORDER BY distance',
            [json_encode(array_values($queryVector)), $userId, $k],
        );

        return collect($rows)->map(fn ($row) => [
            'note_id' => (int) $row->note_id,
            'distance' => (float) $row->distance,
        ]);
    }

    /**
     * @return list<float>|null
     */
    public function generateVector(string $text): ?array
    {
        $dimensions = (int) config('ai.zettle.embedding.dimensions');
        $model = (string) config('ai.zettle.embedding.model');

        $response = Embeddings::for([trim($text)])
            ->dimensions($dimensions)
            ->generate(model: $model);

        $vector = $response->embeddings[0] ?? null;

        return is_array($vector) ? array_values(array_map('floatval', $vector)) : null;
    }
}
