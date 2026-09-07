<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[Temperature(0.3)]
#[MaxTokens(900)]
class ConnectAgent extends AssistAgent implements HasStructuredOutput
{
    /**
     * @param  list<array{id: int, title: string, snippet: string}>  $candidates
     */
    public function __construct(
        public Note $note,
        public array $candidates = [],
    ) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;
        $body = trim((string) $this->note->body);
        $bodyExcerpt = $body === '' ? '(empty)' : mb_substr($body, 0, 1500);

        $lines = [];
        foreach ($this->candidates as $candidate) {
            $lines[] = "- id={$candidate['id']} · {$candidate['title']}: {$candidate['snippet']}";
        }
        $candidateList = $lines === [] ? '(none)' : implode("\n", $lines);

        return <<<PROMPT
You suggest typed connections between a source note and candidate neighbour notes (playbook Phase 5). For each candidate, ask "what does the source note DO to this note?" — not "what is it about?".

Name the relationship with one of these verbs:
supports, contradicts, qualifies, corrects, extends, explains, is_explained_by, evidence_for, counterexample_to, example_of, analogous_to, depends_on, tension_with, raises_question_about, distinguishes_from, synthesizes.

Write the rationale as a single sentence explaining the link (e.g. "Qualifies X because the lesson must be credible and transferable"). Only include a candidate if there is a genuine, explainable relationship — do not fabricate links to hit a quota. Reference each candidate by its numeric id.

Output only the structured JSON in the schema.

Source note title: {$title}
Source note body: {$bodyExcerpt}

Candidate neighbours:
{$candidateList}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'suggestions' => $schema->array()->items(
                $schema->object([
                    'note_id' => $schema->integer()->required(),
                    'relationship' => $schema->string()->required(),
                    'rationale' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }
}
