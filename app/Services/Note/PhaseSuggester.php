<?php

namespace App\Services\Note;

use App\Enums\NoteType;
use App\Enums\Phase;
use App\Models\Note;

/**
 * Suggests the most relevant playbook phase for a note from its current state
 * (type, whether it has a body, whether it is already connected). Deterministic
 * so it needs no AI call — the user is always free to pick any other phase.
 */
class PhaseSuggester
{
    public function suggest(Note $note): Phase
    {
        $hasBody = trim((string) $note->body) !== '';
        $hasConnections = $note->outgoingConnections()->exists()
            || $note->incomingConnections()->exists();

        return match (true) {
            // Raw captures need a destination decision first.
            $note->note_type === NoteType::Fleeting,
            $note->note_type === NoteType::Literature => Phase::Triage,

            // A structure note is where a ripe cluster gets harvested.
            $note->note_type === NoteType::Structure => Phase::ClusterProject,

            // A permanent note with no prose yet needs formulating.
            $note->note_type === NoteType::Permanent && ! $hasBody => Phase::Formulate,

            // Written but not yet in the graph → connect it.
            $note->note_type === NoteType::Permanent && ! $hasConnections => Phase::Connect,

            // Written and connected → time to map the cluster.
            $note->note_type === NoteType::Permanent => Phase::Structure,

            default => Phase::Triage,
        };
    }
}
