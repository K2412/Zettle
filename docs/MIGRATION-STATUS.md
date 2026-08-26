# Zettle — Livewire → React/Inertia migration · status & handoff

Living handoff for the port of the Livewire app **`zettle`** to this React + Inertia app
**`zettle-inertia`**. Read this first when resuming in a new session.

## Goal

Recreate the Livewire Zettelkasten app (`/Users/kevinkabeya/Documents/Zettle/zettle`) as a
**React + Inertia v3** app (`/Users/kevinkabeya/Documents/Zettle/zettle-inertia`), one vertical slice
at a time, preserving features and domain behaviour while modernising the stack. Backend logic
(services, actions, models, enums, jobs, policies) ports largely as-is; the view layer (Blade/Alpine)
is rebuilt in React, and Livewire components become thin controllers returning `Inertia::render`.

## Stack

Laravel 13 · PHP 8.4 (Herd) · Inertia v3 · React 19 (React Compiler ON) · TypeScript · shadcn/ui ·
Wayfinder (typed routes) · Tailwind v4 · Pest 5 (+ Pest browser / Playwright, headless) ·
`DB_CONNECTION=sqlite` (local-first) · `SCOUT_DRIVER=database` · `laravel/ai` + `sqlite-vec`.

## How we work (the process — keep using it)

- **One slice per `/pair` run.** Each slice goes through the full pipeline: research → (sketch) →
  grill → spec → plan-review → implement (TDD subagent) → code-review (2-axis) → taste-review → polish.
- **Planning lives in GitHub `K2412/planning`** (private), never in this code repo. Each slice is an
  epic (`spec:epic`, `stack:react`) with atomic `spec:task` sub-issues; `needs-human` gates on
  dependency installs / migrations / secrets; `blocked` chains the order.
- **Git: one branch per slice** off `main` (`notes-core`, `connections`, `search-embeddings`, …),
  merged to `main` with `--no-ff` when the pipeline completes. **Nothing is pushed — there is no
  remote.** Commits end with the `Co-Authored-By: Claude Opus 4.8 (1M context)` trailer.
- **Test seams:** unit (services/actions/enums), Pest feature with `AssertableInertia` (controllers +
  props + validation + auth), Pest browser (client-only behaviour). External services (the AI SDK) are
  **faked deterministically** in tests — no network.
- **Grounding docs in-repo:** `docs/architecture-inertia.md` (the stack rulebook), `CONTEXT.md`
  (domain glossary), `docs/adr/*` (decisions), `docs/research/*`, `docs/sketches/*`.

## Done — merged to `main`

### Slice 1 · Notes-core (epic #993)
Note list + CodeMirror 6 markdown editor. Index (card list, create-with-type-picker, live search — later
replaced, tag-filter, delete), show (editor with debounced autosave + status, `[[wikilink]]`
autocomplete via `/notes/search`, write/preview tabs with react-markdown + resolve-and-navigate,
tags + links/backlinks sidebar). Backend: `Note`/`Tag`/`Connection` models, `NoteType`/`Relationship`
enums, migrations, `NoteService`, `TagService`, `DeleteNote`, `NotePolicy`, `NoteSearchController`,
Scout (database driver). ADR-0001 (CodeMirror + Scout db driver, Meilisearch deferred).

### Slice 2 · Connections (epic #1002)
Manual **typed connections** — connect two notes with a relationship (grouped Evidential / Structural /
Dialectical vocabulary) + optional rationale. Stored once as a directed edge; the target note shows a
**computed inverse label** ("supported by"), symmetric types read the same both ways (ADR-0002). A
Connections sidebar panel distinct from `[[` mentions; inline `+Connect` (reuses `/notes/search`);
edit-in-place + remove. `Relationship` enum carries the label/inverse/kind data table.

### Slice 4 · Search + embeddings (epic #1009)
Note **embeddings** via the Laravel AI SDK (OpenAI `text-embedding-3-small`, 1536-dim) stored in
**sqlite-vec** (local-first; pgvector/Postgres rejected — ADR-0003). **Explicit** hybrid search (a
Search button, not live) blending Scout keyword + sqlite-vec KNN via **Reciprocal Rank Fusion**. A
`notes:embed --all` backfill. A **Find connections** discovery modal (KNN suggestions with similarity)
that opens the +Connect form pre-filled. AI SDK faked in tests; sqlite-vec loads via PHP 8.4
`Pdo\Sqlite::loadExtension` (Herd) locally, asg017/sqlite-vec in CI.

### Slice · Graph view (epic #1018)
Interactive **force-directed graph** of the note graph — ported from the source's `NoteGraphService` +
Alpine `note-graph.js`. `NoteGraphService::buildGraphData` shapes one eager-loaded, user-scoped read
into `{ nodes, edges }`; each edge carries a `kind` (`mention` vs `typed`) off the connection's
relationship. A thin `NoteGraphController` renders `notes/graph`; the route sits **before**
`notes/{note:slug}` so the slug binding can't capture "graph". Client: a `useGraphSimulation` hook runs
**`d3-force`** (Barnes-Hut charge + link + centre) on a `<canvas>`, all per-frame state in a `ref`
(never `useState`) — typed edges solid, mentions faint-dashed, nodes tag-colored, hover label
theme-aware; drag repositions, click → Inertia visit to the note. A "Graph" sidebar item. Zero new deps
beyond `d3-force`. **No** embedding-similarity edges (Discovery owns that); no zoom/pan/filters/ego-graph
(out of scope). Added `design-patterns/patterns.md` as the house-style anchor.

### Slice 6 · AI Assists — run 1 of ~4 (epic #1062)
The **assist panel** + **Atomize**, built as the pattern the remaining six assists follow. The panel
mounts at the reserved `assist-panel-stub` in the notes/show sidebar: a deterministic `PhaseSuggester`
(no AI) guesses the note's playbook `Phase`, shown as a row of tabs (all 7 render; unwired ones show a
quiet placeholder), the suggested one marked and active on mount — `suggestedPhase` + `phases` ship as
plain props on notes/show. **Atomize** establishes the **two-rail seam** (ADR-0005): the read-only AI
`run()` is a background `fetch` to a JSON endpoint (suggestions live in ephemeral React state; the note
is never touched), while the `spawn` write is an Inertia `<Form>` that creates one empty **permanent
note** per accepted idea + a `Provenance` connection back to the origin, then `back()`s (the origin's
incoming-connections panel refreshes for free). Backend: `Phase` enum, `PhaseSuggester`, `AssistAgent`
base + `AtomizeAgent` (Laravel AI SDK, structured output), `AtomizeAssist` (`run` + `spawnPermanent`,
which reuses `NoteService::createForUser`), `AtomizeController` (POST `run` + `spawn`) + `SpawnRequest`.
AI SDK **faked** deterministically via `AssistsFakeServiceProvider` (mirrors `EmbeddingsFakeServiceProvider`:
local/testing + keyless → fake; keyless staging/prod fails loud). ADR-0005 records assists are read-only
to note content and the two-rail seam. Assists are read-only to note content — they suggest, spawn
siblings, and set metadata, but never rewrite the viewed note's title/body.

### Slice 6 · AI Assists — run 2 of ~4 (epic #1075)
**Triage** + **Formulate**, following the run-1 two-rail seam (ADR-0005 unchanged — no new ADR).
**Triage** reads a note and suggests a **triage destination** + a note type; its read is a background
`fetch` into ephemeral state (the note untouched), and its one write is the set-type Inertia `<Form>`
on the apply-type action — it sets `note_type` metadata only, never the title or body, and flashes a
toast. **Formulate** is the **read-only / no-write** assist: it persists nothing at all. Its **scaffold
templates** (the eight type skeletons) now live **client-side** as a static TS constant
(`resources/js/lib/formulate-templates.ts`, ported verbatim from the source) — no AI, no round-trip,
copy-to-clipboard; its **draft critique** is a read-only `fetch` returning prose. Backend (already
landed): `TriageAgent`/`FormulateAgent`, `TriageAssist` (`run` + `applyType`) / `FormulateAssist`
(`evaluate` only), `TriageController` (POST `run` + `applyType`) + `ApplyTypeRequest`,
`FormulateController` (POST `evaluate`). The fake SDK is now **per-agent** in `AssistsFakeServiceProvider`
(structured agents get array payloads; the prose `FormulateAgent` gets flat strings). Client: the panel
switch now maps triage/atomize/formulate to their children, else the placeholder.

### Slice · Tags (richer management) (epic #1063)
Gave tags a home: a dedicated **`/tags`** page (a "Tags" nav item beside Notes/Graph) listing every
tag with its color and usage count, plus **rename**, **recolor** (free hex), **delete**, and **merge**.
Net-new — the source Livewire app never built richer tag management (both apps sat at the same
attach/detach/filter baseline), so this was built in the migration's spirit, not ported. Thin
`TagController` (index/update/destroy) + a separate `TagMergeController` at `tags.*` routes; a new
`TagPolicy` owns the per-tag ownership gate. Rename **re-slugs** the tag (the slug is the note-tagging
dedup key) and a colliding rename is **rejected** with a merge hint, never silently merged. Colors are
a native `<input type="color">` validated server-side (`/^#[0-9a-f]{6}$/i`, stored lowercased) — **no
migration**, the `color` column already fit. Merge is a `MergeTags` action in a `DB::transaction`
(`syncWithoutDetaching` the target onto the source's notes, then delete the source; shared notes
dedupe, source dies / target survives). `NoteService::tagsForUser` now delegates to
`TagService::listForUser` (one query). ADR-0004. **Out of scope (deferred to AI Assists):** the
`NoteTagSuggester` AI.

**Test totals on `main`:** ~216 feature/unit + ~53 browser, all green.

## Gotchas / standing constraints

- **Local-first is a deliberate stance.** We stayed on SQLite and rejected the AI SDK's native
  pgvector vector search (ADR-0003) to avoid an app-wide Postgres migration. Don't quietly switch DBs.
- **sqlite-vec is a native extension, hard-required** (ADR-0003). Config `database.connections.sqlite.
  extensions.vec` (auto-discovers `database/extensions/vec0.*`, or `SQLITE_VEC_EXTENSION_PATH`). A
  configured-but-missing path now throws. CI must install it.
- **`OPENAI_API_KEY`** is only needed for real embedding generation. Tests fake the SDK. Locally,
  `EmbeddingsFakeServiceProvider` installs a deterministic fake **only** in `local`/`testing` (allowlist)
  when no key is set — a keyless staging/prod fails loud rather than serving fake vectors.
- **React Compiler is ON** — do not add `useMemo`/`useCallback`/`memo` by default.
- **Mutations go through Inertia** (`<Form>`/`router`); background JSON lookups (search, discovery) use
  a plain fetch mirroring `resources/js/lib/note-search.ts`.
- **The assist panel** now mounts at the (formerly reserved) `assist-panel-stub` in the notes/show
  sidebar. It renders all 7 phase tabs; **Triage**, **Atomize**, and **Formulate** are wired, the other
  four are placeholders until their runs land. The two-rail seam (ADR-0005) is the template: read-only
  AI `run()` via background `fetch`, writes via Inertia — copy it, don't reinvent it. **Formulate** is
  the read-only exception: it writes nothing, and its scaffold templates are client-side static text.
- **AI SDK is faked like embeddings.** `AssistsFakeServiceProvider` mirrors `EmbeddingsFakeServiceProvider`
  exactly. `ANTHROPIC_API_KEY` (config `ai.providers.anthropic.key`) is only needed for real synthesis;
  a keyless staging/prod fails loud rather than serving canned ideas. Each new assist adds its agent's
  fake to that provider.

## Next — remaining slices (each its own `/pair`)

Recommended order and notes:

1. **AI Assists (7) — runs 1–2 of ~4 shipped (Atomize + the panel shell, then Triage + Formulate);
   runs 3–4 remain.** The panel, `Phase` enum, `PhaseSuggester`, the `AssistAgent` base, the fake-SDK
   seam, and **Atomize** landed in run 1 (epic #1062); **Triage** + **Formulate** landed in run 2
   (bringing the `TriageDestination` enum and the client-side scaffold templates). Remaining, grouped by
   dependency (each its own `/pair`):
   - **Run 3** — **Connect** (reuses the shipped `NoteConnectionDiscoveryService`) + **MakeFindable**
     (adds a `discovery_hint` migration + the `NoteTagSuggester` AI).
   - **Run 4** — port `NoteClusterService`, then **Structure** + **ClusterProject** (cluster-dependent,
     heaviest). Each new assist = an `AtomizeAgent`-shaped agent + an `AtomizeAssist`-shaped service +
     a controller pair on the two rails + a React child slotted into the panel; add its fake to
     `AssistsFakeServiceProvider`.

Also deferred within done slices (pick up when relevant): note-type change after creation; the
embeddings discovery "already-connected" exclusion polish; Meilisearch upgrade (ADR-0001, only if
keyword relevance becomes a problem — cuts against local-first).

## How to resume

Start a fresh `/pair` for the next slice, e.g.: *"/pair — build the Graph view slice for zettle-inertia,
porting the source's note-graph. Read docs/MIGRATION-STATUS.md, docs/architecture-inertia.md, CONTEXT.md
first."* The pipeline will research → grill → spec (new epic in K2412/planning) → build. Branch off
`main`; merge back when the pipeline completes.
