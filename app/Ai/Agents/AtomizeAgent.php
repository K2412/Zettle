<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[Temperature(0.3)]
#[MaxTokens(800)]
class AtomizeAgent extends AssistAgent implements HasStructuredOutput
{
    public function __construct(public Note $note) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;
        $body = trim((string) $this->note->body);
        $bodyExcerpt = $body === '' ? '(empty)' : mb_substr($body, 0, 3000);

        return <<<PROMPT
You help split a raw note into its distinct ideas (playbook Phase 2). A permanent note captures ONE idea (one center of gravity), not one sentence.

Split when any of these holds: one claim-title can't cover the whole thing; parts would link to different notes; someone could accept one part and reject another; parts need different evidence; a part is reusable without the others. Do not over-atomize into contextless fragments, and do not keep two claims together just because they share a topic.

For each distinct idea, propose a candidate claim-title (a sentence stating the claim, not a topic label) and a one-line rationale for why it is its own note. You do NOT write the note bodies — the user does that.

Output only the structured JSON in the schema.

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
            'ideas' => $schema->array()->items(
                $schema->object([
                    'title' => $schema->string()->required(),
                    'rationale' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }
}
