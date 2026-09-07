<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\MakeFindableAgent;
use App\Ai\Agents\NoteTagSuggester;
use App\Models\Note;
use App\Models\Tag;
use App\Services\Tag\TagService;

class MakeFindableAssist
{
    public function __construct(
        private TagService $tags,
    ) {}

    /**
     * Suggest how to make a note rediscoverable: future retrieval contexts, a
     * discovery hint, and tags (via the reused NoteTagSuggester). Read-only
     * (ADR-0003): returns suggestions only; nothing is written.
     *
     * @return array{retrieval_contexts: list<string>, discovery_hint: string, tags: list<string>}
     */
    public function run(Note $note): array
    {
        $response = MakeFindableAgent::make(note: $note)
            ->prompt('Suggest retrieval contexts and a discovery hint for this note.');

        $note->loadMissing('user');

        $existingTagNames = $note->user
            ->tags()
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();

        $tagResponse = NoteTagSuggester::make(note: $note, existingTagNames: $existingTagNames)
            ->prompt('Suggest tags for this note.');

        return [
            'retrieval_contexts' => array_values(array_map(
                'strval',
                (array) ($response['retrieval_contexts'] ?? [])
            )),
            'discovery_hint' => (string) ($response['discovery_hint'] ?? ''),
            'tags' => array_values(array_map(
                'strval',
                (array) ($tagResponse['tags'] ?? [])
            )),
        ];
    }

    /**
     * Attach a suggested tag on the user's explicit approval. Writes only the
     * note↔tag pivot; the note's title and body are never touched.
     */
    public function attachTag(Note $note, string $tagName): Tag
    {
        return $this->tags->createAndAttachToNote(['name' => $tagName], $note, $note->loadMissing('user')->user);
    }

    /**
     * Set the note's discovery hint on the user's explicit approval. Writes only
     * the discovery_hint column; the note's title and body are never touched.
     */
    public function setDiscoveryHint(Note $note, string $hint): void
    {
        $note->update(['discovery_hint' => $hint]);
    }
}
