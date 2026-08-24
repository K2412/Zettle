<?php

namespace App\Console\Commands;

use App\Jobs\EmbedNoteJob;
use App\Models\Note;
use Illuminate\Console\Command;

/**
 * Backfills embeddings across the whole note graph by queueing an EmbedNoteJob
 * for every existing note, so semantic search and discovery cover notes that
 * predate the embedding pipeline. Idempotent: embed is INSERT OR REPLACE.
 */
class EmbedNotes extends Command
{
    protected $signature = 'notes:embed {--all : Queue embedding for every note}';

    protected $description = 'Queue embedding jobs for existing notes';

    public function handle(): int
    {
        if (! $this->option('all')) {
            $this->error('Nothing to do. Pass --all to embed every note.');

            return self::FAILURE;
        }

        $queued = 0;

        Note::query()->select('id')->chunkById(500, function ($notes) use (&$queued): void {
            foreach ($notes as $note) {
                EmbedNoteJob::dispatch($note->id);
                $queued++;
            }
        });

        $this->info("Queued {$queued} embedding ".str('job')->plural($queued).'.');

        return self::SUCCESS;
    }
}
