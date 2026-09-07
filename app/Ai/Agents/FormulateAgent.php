<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Stringable;

/**
 * Evaluates a draft the user has written and returns critique as prose.
 * No structured output — the response is copy-pastable suggestions the user
 * chooses to apply. It never returns a rewritten note (ADR-0005).
 */
#[Temperature(0.4)]
#[MaxTokens(700)]
class FormulateAgent extends AssistAgent
{
    public function __construct(
        public Note $note,
        public string $draftBody,
    ) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;
        $draft = trim($this->draftBody);
        $draftExcerpt = $draft === '' ? '(empty)' : mb_substr($draft, 0, 3000);

        return <<<PROMPT
You evaluate a draft permanent note the user has written (playbook Phase 3/4). You NEVER rewrite the note for them — you return critique and copy-pastable suggestions the user can choose to apply. Writing the idea for the user would break the method.

Check and comment on, where relevant:
- Distinct moves: is there exactly one primary idea, or should it be split?
- Missing context: pronouns unresolved, terms undefined, acronyms unexpanded.
- Merged source vs. inference: is the source's claim distinguishable from the user's own inference?
- Overclaim: is the claim's strength stronger than its support? Is scope/uncertainty visible?
- Title as claim: does the title state a claim/distinction/mechanism, not a topic label?
- Link candidates: what existing notes might this support/contradict/qualify/extend?

Keep it concrete and short. Suggest wording as quoted prose the user can paste, but never present a full rewritten note.

Note title: {$title}
Draft body:
{$draftExcerpt}
PROMPT;
    }
}
