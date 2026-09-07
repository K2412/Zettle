<?php

use Illuminate\Support\Facades\DB;

it('loads the sqlite-vec extension on the connection', function () {
    $version = DB::selectOne('SELECT vec_version() AS version');

    expect($version->version)->toStartWith('v');
});

it('creates the note_embeddings vec0 table at 1536 dimensions', function () {
    $insert = fn (int $noteId, array $vector) => DB::statement(
        'INSERT OR REPLACE INTO note_embeddings(note_id, user_id, embedding) VALUES (?, ?, vec_f32(?))',
        [$noteId, 1, json_encode(array_pad($vector, 1536, 0.0))],
    );

    $insert(1, [1.0]);

    expect(DB::selectOne('SELECT count(*) AS c FROM note_embeddings')->c)->toBe(1);
});

it('returns the inserted row ordered by distance on a MATCH-k query', function () {
    $insert = fn (int $noteId, array $vector) => DB::statement(
        'INSERT OR REPLACE INTO note_embeddings(note_id, user_id, embedding) VALUES (?, ?, vec_f32(?))',
        [$noteId, 1, json_encode(array_pad($vector, 1536, 0.0))],
    );

    // Note 10 sits on the x-axis, 20 on the y-axis, 30 near the x-axis.
    $insert(10, [1.0, 0.0]);
    $insert(20, [0.0, 1.0]);
    $insert(30, [0.9, 0.1]);

    $rows = DB::select(
        'SELECT note_id, distance FROM note_embeddings WHERE embedding MATCH ? AND user_id = ? AND k = ? ORDER BY distance',
        [json_encode(array_pad([1.0, 0.0], 1536, 0.0)), 1, 2],
    );

    expect($rows)->toHaveCount(2)
        ->and((int) $rows[0]->note_id)->toBe(10)
        ->and((int) $rows[1]->note_id)->toBe(30)
        ->and((float) $rows[0]->distance)->toBeLessThan((float) $rows[1]->distance);
});
