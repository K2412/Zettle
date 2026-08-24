export type NoteType =
    'fleeting' | 'literature' | 'permanent' | 'structure' | 'project';

export type Tag = {
    id: number;
    name: string;
    color: string;
    notes_count?: number;
};

export type Note = {
    id: number;
    title: string;
    slug: string;
    body: string | null;
    note_type: NoteType;
    created_at: string;
    updated_at: string;
    tags?: Tag[];
};

export type NoteLink = {
    id: number;
    title: string;
    slug: string;
};

/** One playbook phase the assist panel can run, serialized from the Phase enum. */
export type PhaseOption = {
    value: string;
    label: string;
};

/** One idea the Atomize assist proposes: a candidate note title and why it stands alone. */
export type AtomizeIdea = {
    title: string;
    rationale: string;
};

/**
 * The Triage assist's read-only suggestion: where the note should go
 * (`destination`, a raw enum value like `project_only`), the note type it
 * recommends, and the reasoning behind both. Applying the type is a separate,
 * explicit write.
 */
export type TriageResult = {
    destination: string;
    note_type: NoteType;
    reasoning: string;
};

/** One discovery suggestion: a note related by embedding nearness, with score. */
export type DiscoverySuggestion = NoteLink & {
    snippet: string;
    similarity: number;
};

/** One directed typed connection, pre-labelled server-side. */
export type Connection = {
    id: number;
    note: NoteLink;
    relationship: string;
    label: string;
    rationale: string | null;
};

/** One relationship option in the authored vocabulary, serialized from the enum. */
export type RelationshipOption = {
    value: string;
    label: string;
};

/** The authored relationship vocabulary, grouped by kind (one source of truth). */
export type RelationshipGroup = {
    group: string;
    options: RelationshipOption[];
};

/** One node in the note graph: a note, tag-colored. */
export type GraphNode = {
    id: number;
    title: string;
    slug: string;
    color: string;
};

/** One edge in the note graph. `mention` = wikilink, `typed` = authored connection. */
export type GraphEdge = {
    source: number;
    target: number;
    kind: 'mention' | 'typed';
};

/** The whole note graph: every note as a node, every link as an edge. */
export type GraphData = {
    nodes: GraphNode[];
    edges: GraphEdge[];
};

export type NoteFilters = {
    q: string;
    tagId: number | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
};
