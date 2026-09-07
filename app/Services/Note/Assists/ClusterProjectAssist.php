<?php

namespace App\Services\Note\Assists;

use App\Ai\Agents\ClusterProjectAgent;
use App\Enums\NoteType;
use App\Enums\Relationship;
use App\Models\Connection;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteClusterService;
use Illuminate\Support\Str;

class ClusterProjectAssist
{
    public function __construct(
        private NoteClusterService $clusters,
    ) {}

    /**
     * Surface ripe clusters, each with a suggested ordering and — for the
     * top-ranked cluster only — an eager LLM readiness assessment and flagged
     * gaps. The remaining clusters are returned unassessed so a single Livewire
     * request never blocks on N sequential Anthropic calls; a follow-up assist
     * run (or a future queued job) can assess the rest. Read-only (ADR-0003):
     * returns suggestions; nothing is written.
     *
     * @return list<array{note_ids: list<int>, score: float, assessment: string, suggested_order: list<int>, gaps: list<string>}>
     */
    public function run(User $user): array
    {
        // ripeClusters() is already ordered richest-first, so index 0 is the top.
        $ripe = $this->clusters->ripeClusters($user)->values();

        $result = [];

        foreach ($ripe as $index => $cluster) {
            // Only the top-ranked cluster is assessed eagerly (one LLM call max).
            $result[] = $index === 0
                ? $this->assess($cluster)
                : $this->unassessed($cluster);
        }

        return $result;
    }

    /**
     * Run the (single) eager LLM assessment for one cluster.
     *
     * @param  array{note_ids: list<int>, score: float}  $cluster
     * @return array{note_ids: list<int>, score: float, assessment: string, suggested_order: list<int>, gaps: list<string>}
     */
    private function assess(array $cluster): array
    {
        $notes = Note::query()
            ->whereIn('id', $cluster['note_ids'])
            ->get(['id', 'title', 'body']);

        $forAgent = $notes->map(fn (Note $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'snippet' => Str::limit(strip_tags((string) $n->body), 200),
        ])->all();

        $response = ClusterProjectAgent::make(clusterNotes: $forAgent)
            ->prompt('Assess this cluster for a project.');

        $order = array_values(array_map('intval', (array) ($response['suggested_order'] ?? [])));
        $gaps = array_values(array_map('strval', (array) ($response['gaps'] ?? [])));

        return [
            'note_ids' => $cluster['note_ids'],
            'score' => $cluster['score'],
            'assessment' => (string) ($response['assessment'] ?? ''),
            'suggested_order' => $order === [] ? $cluster['note_ids'] : $order,
            'gaps' => $gaps,
        ];
    }

    /**
     * A ripe cluster surfaced without an LLM assessment.
     *
     * @param  array{note_ids: list<int>, score: float}  $cluster
     * @return array{note_ids: list<int>, score: float, assessment: string, suggested_order: list<int>, gaps: list<string>}
     */
    private function unassessed(array $cluster): array
    {
        return [
            'note_ids' => $cluster['note_ids'],
            'score' => $cluster['score'],
            'assessment' => '',
            'suggested_order' => $cluster['note_ids'],
            'gaps' => [],
        ];
    }

    /**
     * Create a disposable project note that references the cluster's permanent
     * notes in the given order. Explicit user action; the permanent notes are
     * never copied, consumed, or altered — only mentions connections are added
     * from the project note to them (order preserved via the connection order).
     *
     * @param  list<int>  $orderedNoteIds
     */
    public function createProjectNote(User $user, string $title, array $orderedNoteIds): Note
    {
        $title = trim($title) === '' ? 'Project' : trim($title);

        $project = Note::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'body' => '',
            'note_type' => NoteType::Project,
        ]);

        foreach ($orderedNoteIds as $targetId) {
            Connection::query()->firstOrCreate([
                'source_note_id' => $project->id,
                'target_note_id' => (int) $targetId,
                'relationship' => Relationship::Mentions,
            ], [
                'user_id' => $user->id,
            ]);
        }

        return $project;
    }
}
