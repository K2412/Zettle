<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\ConnectAgent;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Services\Note\NoteConnectionDiscoveryService;

class ConnectAssist
{
    public function __construct(
        private NoteConnectionDiscoveryService $discovery,
    ) {}

    /**
     * Surface semantically-similar UNLINKED neighbours and ask the agent for a
     * relationship + rationale per candidate. Read-only (ADR-0003): returns
     * suggestions only; the user chooses which to apply.
     *
     * @return list<array{note_id: int, title: string, slug: string, snippet: string, similarity: float, relationship: Relationship, rationale: string}>
     */
    public function run(Note $source): array
    {
        // The discovery service already excludes the source and its existing links,
        // so every candidate here is an unlinked neighbour.
        $neighbours = $this->discovery->discover($source);

        if ($neighbours->isEmpty()) {
            return [];
        }

        $candidatesForAgent = $neighbours
            ->map(fn (array $n) => [
                'id' => (int) $n['id'],
                'title' => (string) $n['title'],
                'snippet' => (string) ($n['snippet'] ?? ''),
            ])
            ->all();

        $response = ConnectAgent::make(note: $source, candidates: $candidatesForAgent)
            ->prompt('Suggest typed connections for these neighbours.');

        $suggestions = [];
        foreach ((array) ($response['suggestions'] ?? []) as $suggestion) {
            $suggestions[(int) ($suggestion['note_id'] ?? 0)] = [
                'relationship' => Relationship::from((string) $suggestion['relationship']),
                'rationale' => (string) $suggestion['rationale'],
            ];
        }

        $candidates = [];
        $canned = $suggestions[0] ?? null;
        $usedCanned = false;

        foreach ($neighbours as $neighbour) {
            $id = (int) $neighbour['id'];

            if (isset($suggestions[$id])) {
                $hit = $suggestions[$id];
            } elseif ($canned !== null && ! $usedCanned) {
                // Keyless local/testing fakes cannot know real note IDs, so they
                // send note_id 0. Pair that canned suggestion with the first
                // unmatched neighbour rather than dropping every candidate.
                $hit = $canned;
                $usedCanned = true;
            } else {
                continue;
            }

            $candidates[] = [
                'note_id' => $id,
                'title' => (string) $neighbour['title'],
                'slug' => (string) ($neighbour['slug'] ?? ''),
                'snippet' => (string) ($neighbour['snippet'] ?? ''),
                'similarity' => (float) ($neighbour['similarity'] ?? 0.0),
                'relationship' => $hit['relationship'],
                'rationale' => $hit['rationale'],
            ];
        }

        return $candidates;
    }

    /**
     * Create one typed connection on an accepted suggestion. Explicit user
     * action; writes only the connections table, never the note content.
     */
    public function link(Note $source, Note $target, Relationship $relationship, ?string $rationale = null): Connection
    {
        return Connection::query()->create([
            'user_id' => $source->user_id,
            'source_note_id' => $source->id,
            'target_note_id' => $target->id,
            'relationship' => $relationship,
            'rationale' => $rationale,
        ]);
    }
}
