<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[Temperature(0.2)]
#[MaxTokens(400)]
class TriageAgent extends AssistAgent implements HasStructuredOutput
{
    public function __construct(public Note $note) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;
        $body = trim((string) $this->note->body);
        $bodyExcerpt = $body === '' ? '(empty)' : mb_substr($body, 0, 1500);

        return <<<PROMPT
You are a Zettelkasten triage partner. Decide the note's destination — ask "what should happen to this?", not "is this interesting?". You never write or rewrite the note.

Pick exactly one destination:
- discard: no longer understandable, trivial, or redundant.
- task: requires an action (belongs outside the note system).
- project_only: only matters to one deliverable.
- keep_literature: faithfully represents a source but adds no idea of yours yet.
- develop: changes, challenges, extends, or explains something you think — becomes a permanent note.
- question: raises a live unresolved question (a permanent note whose idea is the question).
- multi_idea: holds several distinct ideas and should be split.

Also suggest a note_type from: fleeting, literature, permanent, structure, project.
Give one sentence of reasoning. Output only the structured JSON in the schema.

Note title: {$title}
Note body: {$bodyExcerpt}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'destination' => $schema->string()->required(),
            'note_type' => $schema->string()->required(),
            'reasoning' => $schema->string()->required(),
        ];
    }
}
