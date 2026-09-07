<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[Temperature(0.4)]
#[MaxTokens(500)]
class MakeFindableAgent extends AssistAgent implements HasStructuredOutput
{
    public function __construct(
        public Note $note,
    ) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;
        $body = trim((string) $this->note->body);
        $bodyExcerpt = $body === '' ? '(empty)' : mb_substr($body, 0, 1500);

        return <<<PROMPT
You make a permanent note findable again in the future (playbook Phase 6 — Make-findable). A note is only useful if the user rediscovers it when they need it, not when they remember it exists.

Produce:
- 3 to 5 retrieval contexts: the future questions, phrasings, or situations in which the user would want THIS note to surface. Write them as natural questions or search phrases the user's future self would type, not as tags or keywords.
- a single discovery_hint: one short sentence telling the future self when to reach for this note (e.g. "Reach for this when deciding how to space study sessions.").

Do not restate the note's content. Do not write the note for the user. Output only the structured JSON in the schema.

Note title: {$title}
Note body: {$bodyExcerpt}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'retrieval_contexts' => $schema->array()
                ->items($schema->string())
                ->required(),
            'discovery_hint' => $schema->string()->required(),
        ];
    }
}
