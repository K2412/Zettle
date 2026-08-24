<?php

namespace App\Services\Note;

use App\Enums\NoteType;
use App\Enums\Phase;
use App\Enums\Relationship;
use App\Jobs\EmbedNoteJob;
use App\Models\Connection;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\Tag\TagService;
use App\Support\Search\ReciprocalRankFusion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NoteService
{
    public function __construct(
        private NoteEmbeddingService $embeddings,
        private PhaseSuggester $phaseSuggester,
        private TagService $tags,
    ) {}

    /**
     * A non-empty query runs hybrid search: Scout keyword matches and sqlite-vec
     * KNN over the query's embedding, fused by Reciprocal Rank Fusion so a note
     * that either words OR meaning surfaces ranks well. An empty query skips the
     * embedding call entirely and returns the latest notes.
     *
     * @return LengthAwarePaginator<int, Note>
     */
    public function listForUser(User $user, string $search = '', ?int $tagId = null, int $perPage = 20): LengthAwarePaginator
    {
        if ($search === '') {
            return Note::query()
                ->where('user_id', $user->id)
                ->with('tags')
                ->when($tagId, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', $tagId)))
                ->latest()
                ->paginate($perPage);
        }

        return $this->hybridSearch($user, $search, $tagId, $perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Note>
     */
    private function hybridSearch(User $user, string $query, ?int $tagId, int $perPage): LengthAwarePaginator
    {
        // Tag-scope both arms BEFORE the cap so a tag filter can't drop a matching
        // note that ranked 51+, and so the fused total stays accurate. The keyword
        // arm filters inside the Scout query; the vec0 KNN can't join tags, so its
        // candidates are filtered against the tagged id set here. (50 per arm is a
        // deliberate ceiling.)
        $keywordIds = Note::search($query)
            ->query(fn ($q) => $q
                ->where('user_id', $user->id)
                ->when($tagId, fn ($qq) => $qq->whereHas('tags', fn ($t) => $t->where('tags.id', $tagId)))
            )
            ->take(50)
            ->keys()
            ->all();

        $vector = $this->embeddings->generateVector($query);

        $vectorIds = $vector === null
            ? []
            : $this->embeddings->knn($vector, $user->id, 50)->pluck('note_id')->all();

        if ($tagId !== null && $vectorIds !== []) {
            $tagged = Note::query()
                ->where('user_id', $user->id)
                ->whereHas('tags', fn ($t) => $t->where('tags.id', $tagId))
                ->whereIn('id', $vectorIds)
                ->pluck('id')
                ->flip();

            $vectorIds = array_values(array_filter($vectorIds, fn (int $id) => $tagged->has($id)));
        }

        /** @var list<int> $rankedIds */
        $rankedIds = ReciprocalRankFusion::fuse([$keywordIds, $vectorIds]);

        return $this->paginateByRankedIds($user, $rankedIds, $perPage);
    }

    /**
     * Load the notes for the already-scoped fused id list in that exact order and
     * hand back a real paginator over the ranking.
     *
     * @param  list<int>  $rankedIds
     * @return LengthAwarePaginator<int, Note>
     */
    private function paginateByRankedIds(User $user, array $rankedIds, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        if ($rankedIds === []) {
            return new Paginator([], 0, $perPage, $page, ['path' => Paginator::resolveCurrentPath()]);
        }

        // Both search arms are already tag-scoped, so rankedIds is the final set.
        $notes = Note::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $rankedIds)
            ->with('tags')
            ->get()
            ->keyBy('id');

        $ordered = collect($rankedIds)
            ->map(fn (int $id) => $notes->get($id))
            ->filter()
            ->values();

        $slice = $ordered->forPage($page, $perPage)->values();

        return new Paginator($slice, $ordered->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * @return Collection<int, Tag>
     */
    public function tagsForUser(User $user): Collection
    {
        return $this->tags->listForUser($user);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(array $data, User $user): Note
    {
        return Note::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::random(6),
            'body' => '',
            'note_type' => $data['note_type'] ?? NoteType::Fleeting,
        ]);
    }

    /**
     * Assemble the show-page prop bag: the note with its tags, its `mentions`
     * links and backlinks, its typed connections both directions, the relationship
     * vocabulary, the tags still available to attach, a title→slug map the editor
     * uses to resolve `[[wikilinks]]` in preview, the assist phase suggested for
     * this note, and the full phase vocabulary (value + label) the panel offers.
     *
     * @return array<string, mixed>
     */
    public function showData(Note $note): array
    {
        $note->load([
            'tags',
            'outgoingConnections' => fn ($q) => $q
                ->where('relationship', '!=', Relationship::Mentions)
                ->with('target:id,title,slug')
                ->orderBy('relationship'),
            'incomingConnections' => fn ($q) => $q
                ->where('relationship', '!=', Relationship::Mentions)
                ->with('source:id,title,slug')
                ->orderBy('relationship'),
        ]);

        // `mentions` edges only — the [[wikilink]] lists. Scoped so authored typed
        // edges never leak in, and read per-edge (not distinct) is unnecessary here
        // since a pair mentions at most once.
        $outgoingLinks = $note->linksTo()
            ->wherePivot('relationship', Relationship::Mentions->value)
            ->select(['notes.id', 'notes.title', 'notes.slug'])
            ->distinct()
            ->orderBy('notes.title')
            ->get();

        $backlinks = $note->linkedFrom()
            ->wherePivot('relationship', Relationship::Mentions->value)
            ->select(['notes.id', 'notes.title', 'notes.slug'])
            ->distinct()
            ->orderBy('notes.title')
            ->get();

        // Typed connections read per edge (not the distinct() belongsToMany), so a
        // note that both `supports` and `extends` a target shows BOTH. Outgoing
        // carries the forward label; incoming carries the computed inverse label.
        $connections = $note->outgoingConnections->map(fn (Connection $c) => [
            'id' => $c->id,
            'note' => $c->target->only(['id', 'title', 'slug']),
            'relationship' => $c->relationship->value,
            'label' => $c->relationship->label(),
            'rationale' => $c->rationale,
        ])->values();

        $incomingConnections = $note->incomingConnections->map(fn (Connection $c) => [
            'id' => $c->id,
            'note' => $c->source->only(['id', 'title', 'slug']),
            'relationship' => $c->relationship->value,
            'label' => $c->relationship->inverseLabel(),
            'rationale' => $c->rationale,
        ])->values();

        // The connection rows travel as their own props; keep them off the note.
        $note->unsetRelation('outgoingConnections')->unsetRelation('incomingConnections');

        $availableTags = Tag::query()
            ->where('user_id', $note->user_id)
            ->whereNotIn('id', $note->tags->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        // Bounds the show payload: preview resolves [[wikilinks]] against this
        // map, capped to the most recently touched notes. Beyond the cap a link
        // renders unresolved until searched — the graph stays navigable.
        $titleToSlug = Note::query()
            ->where('user_id', $note->user_id)
            ->latest('updated_at')
            ->limit(2000)
            ->pluck('slug', 'title');

        return [
            'note' => $note,
            'outgoingLinks' => $outgoingLinks,
            'backlinks' => $backlinks,
            'connections' => $connections,
            'incomingConnections' => $incomingConnections,
            'relationshipOptions' => Relationship::options(),
            'availableTags' => $availableTags,
            'titleToSlug' => $titleToSlug,
            'suggestedPhase' => $this->phaseSuggester->suggest($note)->value,
            'phases' => array_map(
                fn (Phase $phase) => ['value' => $phase->value, 'label' => $phase->label()],
                Phase::cases(),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateWithLinks(Note $note, array $data, User $user): void
    {
        $note->update($data);

        if (isset($data['body'])) {
            $this->syncLinksFromBody($note, $data['body'], $user);
        }

        if ($note->wasChanged(['title', 'body'])) {
            EmbedNoteJob::dispatch($note->id);
        }
    }

    /**
     * @return Collection<int, array{id: int, title: string, slug: string}>
     */
    public function searchForUser(User $user, string $query, ?int $excludeId = null): Collection
    {
        return Note::search($query)
            ->query(fn ($q) => $q
                ->where('user_id', $user->id)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            )
            ->take(5)
            ->get()
            ->map(fn ($note) => ['id' => $note->id, 'title' => $note->title, 'slug' => $note->slug]);
    }

    private function syncLinksFromBody(Note $note, string $body, User $user): void
    {
        preg_match_all('/\[\[(.+?)\]\]/', $body, $matches);
        $linkedTitles = $matches[1] ?? [];

        $linkedIds = Note::query()
            ->where('user_id', $user->id)
            ->whereIn('title', $linkedTitles)
            ->pluck('id');

        // Wikilinks reconcile to `mentions` connections; other relationships are
        // authored explicitly and must survive re-parsing the body.
        Connection::query()
            ->where('source_note_id', $note->id)
            ->where('relationship', Relationship::Mentions)
            ->whereNotIn('target_note_id', $linkedIds)
            ->delete();

        foreach ($linkedIds as $targetId) {
            Connection::query()->firstOrCreate([
                'source_note_id' => $note->id,
                'target_note_id' => $targetId,
                'relationship' => Relationship::Mentions,
            ], [
                'user_id' => $user->id,
            ]);
        }
    }
}
