<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\TriageAgent;
use App\Enums\NoteType;
use App\Enums\TriageDestination;
use App\Models\Note;

class TriageAssist
{
    /**
     * Classify the note's destination and suggest a note type. Read-only
     * (ADR-0005): the agent only reads the note and returns a suggestion — it
     * never touches the origin.
     *
     * @return array{destination: TriageDestination, note_type: NoteType, reasoning: string}
     */
    public function run(Note $note): array
    {
        $response = TriageAgent::make(note: $note)->prompt('Triage this note.');

        return [
            'destination' => TriageDestination::from((string) $response['destination']),
            'note_type' => NoteType::from((string) $response['note_type']),
            'reasoning' => (string) $response['reasoning'],
        ];
    }

    /**
     * Set the note's type. An explicit user action on an accepted suggestion —
     * the only thing this assist writes, and never the title or body (ADR-0005:
     * triage writes note_type metadata only).
     */
    public function applyType(Note $note, NoteType $type): void
    {
        $note->forceFill(['note_type' => $type])->save();
    }
}
