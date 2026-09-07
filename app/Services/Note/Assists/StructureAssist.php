<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\StructureAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Services\Note\NoteClusterService;
use Illuminate\Support\Str;

class StructureAssist
{
    public function __construct(
        private NoteClusterService $clusters,
    ) {}

    /**
     * Find the cluster the note sits in and scaffold an interpreted structure
     * note for it. Read-only (ADR-0003): returns a copy-pastable scaffold and
     * the cluster membership; nothing is written.
     *
     * @return array{cluster_note_ids: list<int>, central_question: string, scaffold: string, index_entry: string}
     */
    public function run(Note $note): array
    {
        $component = $this->clusters->components($note->loadMissing('user')->user)
            ->first(fn (array $c) => in_array($note->id, $c['note_ids'], true));

        $clusterNoteIds = $component['note_ids'] ?? [$note->id];

        $clusterNotes = Note::query()
            ->whereIn('id', $clusterNoteIds)
            ->get(['id', 'title']);

        $response = StructureAgent::make(
            note: $note,
            clusterNotes: $clusterNotes->map(fn (Note $n) => ['id' => $n->id, 'title' => $n->title])->all(),
        )->prompt('Scaffold a structure note for this cluster.');

        return [
            'cluster_note_ids' => array_values($clusterNoteIds),
            'central_question' => (string) ($response['central_question'] ?? ''),
            'scaffold' => (string) ($response['scaffold'] ?? ''),
            'index_entry' => (string) ($response['index_entry'] ?? ''),
        ];
    }

    /**
     * Create a structure note that references the cluster's permanent notes.
     * Explicit user action; the structure note starts with an empty body for
     * the user to write the interpretation. The cluster notes are never
     * modified — only mentions connections are added from the new note to them.
     *
     * @param  list<int>  $clusterNoteIds
     */
    public function createStructureNote(Note $origin, string $title, array $clusterNoteIds): Note
    {
        $title = trim($title) === '' ? 'Structure — '.$origin->title : trim($title);

        $structure = Note::query()->create([
            'user_id' => $origin->user_id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'body' => '',
            'note_type' => NoteType::Structure,
        ]);

        foreach ($clusterNoteIds as $targetId) {
            Connection::query()->firstOrCreate([
                'source_note_id' => $structure->id,
                'target_note_id' => (int) $targetId,
                'relationship' => Relationship::Mentions,
            ], [
                'user_id' => $origin->user_id,
            ]);
        }

        return $structure;
    }
}
