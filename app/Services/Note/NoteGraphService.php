<?php

namespace App\Services\Note;

use App\Enums\Relationship;
use App\Models\Note;
use App\Models\User;

class NoteGraphService
{
    /**
     * @return array{nodes: list<array{id: int, title: string, slug: string, color: string}>, edges: list<array{source: int, target: int, kind: string}>}
     */
    public function buildGraphData(User $user): array
    {
        $notes = Note::query()
            ->where('user_id', $user->id)
            ->with(['linksTo:id,title,slug', 'tags:id,name,color'])
            ->latest()
            ->get(['id', 'title', 'slug']);

        $nodes = $notes->map(fn (Note $note) => [
            'id' => $note->id,
            'title' => $note->title,
            'slug' => $note->slug,
            'color' => $note->tags->first()?->color ?? '#6b7280',
        ])->values()->all();

        $edges = [];
        $seen = [];
        foreach ($notes as $note) {
            foreach ($note->linksTo as $linkedNote) {
                $kind = $linkedNote->pivot->relationship === Relationship::Mentions
                    ? 'mention'
                    : 'typed';

                $key = $note->id.'-'.$linkedNote->id.'-'.$kind;
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $edges[] = [
                    'source' => $note->id,
                    'target' => $linkedNote->id,
                    'kind' => $kind,
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
