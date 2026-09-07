<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[Temperature(0.4)]
#[MaxTokens(1000)]
class ClusterProjectAgent extends AssistAgent implements HasStructuredOutput
{
    /**
     * @param  list<array{id: int, title: string, snippet: string}>  $clusterNotes
     */
    public function __construct(
        public array $clusterNotes = [],
    ) {}

    public function instructions(): Stringable|string
    {
        $lines = [];
        foreach ($this->clusterNotes as $note) {
            $lines[] = "- id={$note['id']} · {$note['title']}: {$note['snippet']}";
        }
        $clusterList = $lines === [] ? '(none)' : implode("\n", $lines);

        return <<<PROMPT
You assess whether a cluster of permanent notes is ripe to harvest into a PROJECT note (playbook Phase 8) and, if so, scaffold that project — an instrumental, disposable document serving one deliverable.

Assess project-readiness: enough connected notes to actually say something; connections that already sequence into a line of reasoning; open questions resolved or productively framed; a real deliverable in view.

Then:
- Give a short readiness assessment.
- Propose an ordered sequence of the note ids — that ordering is the outline.
- Flag gaps: steps the argument needs but no note covers (each a short sentence).

CRITICAL: never rewrite or copy the permanent notes. A project note only *references* them; the permanent notes stay reusable. Reference notes by their numeric id.

Output only the structured JSON in the schema.

Cluster notes:
{$clusterList}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'assessment' => $schema->string()->required(),
            'suggested_order' => $schema->array()->items($schema->integer())->required(),
            'gaps' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
