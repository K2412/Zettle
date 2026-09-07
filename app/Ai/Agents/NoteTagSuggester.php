<?php

namespace App\Ai\Agents;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-6')]
#[Temperature(0.3)]
#[MaxTokens(300)]
class NoteTagSuggester implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public Note $note,
        /** @var list<string> */
        public array $existingTagNames = [],
    ) {}

    public function instructions(): Stringable|string
    {
        $title = $this->note->title;
        $body = trim((string) $this->note->body);
        $bodyExcerpt = $body === '' ? '(empty)' : mb_substr($body, 0, 1500);

        $existing = $this->existingTagNames === [] ? '(none yet)' : implode(', ', $this->existingTagNames);

        return <<<PROMPT
You suggest 3 to 6 short Zettelkasten tags for a note based on the note text.

Rules:
- Lowercase, kebab-case, 1 to 3 words.
- Prefer reusing tags from the user's existing tag set when they fit.
- Never invent abstract single-letter tags.
- Output only the structured JSON described in the schema; no extra prose.

Existing user tags (reuse these when relevant): {$existing}

Note title: {$title}
Note body: {$bodyExcerpt}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tags' => $schema->array()
                ->items($schema->string())
                ->required(),
        ];
    }
}
