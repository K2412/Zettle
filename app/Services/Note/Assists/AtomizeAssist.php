<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\AtomizeAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Services\Note\NoteService;

class AtomizeAssist
{
    public function __construct(private NoteService $notes) {}

    /**
     * Detect the distinct ideas in a raw note. Read-only (ADR-0005): returns
     * candidate titles + rationales; the user accepts which ones to spawn.
     *
     * @return array{ideas: list<array{title: string, rationale: string}>}
     */
    public function run(Note $note): array
    {
        $response = AtomizeAgent::make(note: $note)->prompt('Find the distinct ideas in this note.');

        $ideas = [];

        foreach ((array) ($response['ideas'] ?? []) as $idea) {
            $ideas[] = [
                'title' => (string) ($idea['title'] ?? ''),
                'rationale' => (string) ($idea['rationale'] ?? ''),
            ];
        }

        return ['ideas' => $ideas];
    }

    /**
     * Spawn one empty permanent note per accepted candidate title, each with a
     * provenance connection back to the origin. Note creation is delegated to the
     * canonical creator ({@see NoteService::createForUser}), which forces an empty
     * body and generates the slug. The origin is never modified — a spawned note's
     * body is empty for the user to write.
     *
     * @param  list<string>  $acceptedTitles
     * @return list<Note>
     */
    public function spawnPermanent(Note $origin, array $acceptedTitles): array
    {
        $spawned = [];

        foreach ($acceptedTitles as $title) {
            $title = trim($title);

            if ($title === '') {
                continue;
            }

            $note = $this->notes->createForUser(
                ['title' => $title, 'note_type' => NoteType::Permanent],
                $origin->user,
            );

            Connection::query()->create([
                'user_id' => $origin->user_id,
                'source_note_id' => $note->id,
                'target_note_id' => $origin->id,
                'relationship' => Relationship::Provenance,
                'rationale' => 'Spawned from '.$origin->title.' during atomize.',
            ]);

            $spawned[] = $note;
        }

        return $spawned;
    }
}
