<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\FormulateAgent;
use App\Models\Note;

class FormulateAssist
{
    /**
     * Evaluate a draft the user has written and return critique as prose. The
     * note is never modified — evaluation is read-only (ADR-0005). Formulate
     * writes nothing at all.
     */
    public function evaluate(Note $note, string $draftBody): string
    {
        $response = FormulateAgent::make(note: $note, draftBody: $draftBody)
            ->prompt('Evaluate this draft.');

        return (string) $response;
    }
}
