<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The note_embeddings vec0 virtual table holds one embedding per note. It
     * lives only on sqlite (the sole supported driver) and needs the sqlite-vec
     * extension, which SqliteVecServiceProvider loads on connect. Per ADR-0003
     * the extension is hard-required: if it isn't loaded the vec0 CREATE throws
     * rather than silently skipping, so a broken vector store fails loudly.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        $dimensions = (int) config('ai.zettle.embedding.dimensions');

        DB::statement(sprintf(
            'CREATE VIRTUAL TABLE IF NOT EXISTS note_embeddings USING vec0('.
            'note_id INTEGER PRIMARY KEY, user_id INTEGER, embedding FLOAT[%d])',
            $dimensions,
        ));
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS note_embeddings');
    }
};
