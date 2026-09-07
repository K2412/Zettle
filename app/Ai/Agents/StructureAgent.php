<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[Temperature(0.4)]
#[MaxTokens(1000)]
class StructureAgent extends AssistAgent implements HasStructuredOutput
{
    /**
     * @param  list<array{id: int, title: string}>  $clusterNotes
     */
    public function __construct(
        public Note $note,
        public array $clusterNotes = [],
    ) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;

        $lines = [];
        foreach ($this->clusterNotes as $clusterNote) {
            $lines[] = "- [[{$clusterNote['title']}]]";
        }
        $clusterList = $lines === [] ? '(none)' : implode("\n", $lines);

        return <<<PROMPT
You scaffold an interpreted STRUCTURE note for a cluster of permanent notes (playbook Phase 7). A structure note is an interpreted map, not a folder listing — group the cluster's notes under the argument they are forming and name the open questions and tensions. You DO NOT write the interpretation for the user; you provide a scaffold with the notes grouped and prompts for the user to fill in.

Reference every cluster note by its exact title using [[Title]] wikilinks. Produce:
- a central question the cluster is trying to resolve,
- a scaffold in markdown grouping the [[links]] under sub-themes with a one-line role prompt each, and an "Open questions / tensions" section,
- optionally, a short index entry if this cluster opens a genuinely new line of inquiry (empty string if not).

Output only the structured JSON in the schema.

Current note: {$title}

Cluster notes:
{$clusterList}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'central_question' => $schema->string()->required(),
            'scaffold' => $schema->string()->required(),
            'index_entry' => $schema->string(),
        ];
    }
}
