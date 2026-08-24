# Assists are read-only to note content, over a two-rail seam

Context: the AI Assists slice adds on-demand AI actions ("assists") to a note — the first being Atomize,
which reads a note holding several ideas and proposes each as its own candidate note. An assist has two
jobs that pull in opposite directions: it must *run AI* to propose (non-idempotent, billable, slow) and
it must *write* when the user accepts (spawn notes, create connections). The app already draws a hard
line (see `docs/architecture-inertia.md`): mutations go through an Inertia visit that returns fresh
props; read-only lookups go through a plain background `fetch` that never swaps the page. Assists have to
sit cleanly on both sides of that line without blurring it. (The source Livewire app recorded this as its
"ADR-0003"; that number is taken here by the sqlite-vec decision, so this is 0005.)

## Decision

**Assists are read-only to a note's own content.** Running an assist never rewrites the origin note's
title or body — not the read rail, not the write rail. Atomize *proposes* candidate notes and, on
accept, *spawns new* permanent notes with a `provenance` connection back to the origin; the origin is
left exactly as the user wrote it. This is a standing constraint on every future assist, not just
Atomize.

**Two rails, split by the mutation line:**

- **Read rail — the AI lookup.** "Find the ideas" is a plain background `fetch` (POST, since AI
  generation is non-idempotent and billable, so not a prefetchable GET) to `AtomizeController::run`,
  which authorizes `view`, runs the agent, and returns ideas as JSON. The suggestions land in
  ephemeral React state and vanish on refresh — they are proposals, not server truth, so the page is
  never swapped and no note is touched. Mirrors the search/discovery fetch helpers
  (`resources/js/lib/note-search.ts` → `resources/js/lib/note-assists.ts`).
- **Write rail — the spawn.** Accepting ideas is an Inertia `<Form>` visit to
  `AtomizeController::spawn`, which authorizes `update` (via the form request), creates the accepted
  notes, flashes a toast, and `return back()`s. The origin's sidebar (its new provenance backlinks)
  refreshes for free from the redirect's fresh props — no bespoke response shape.

## Rejected

- **One rail for both (an Inertia visit to "Find the ideas")** — would swap the page and thread AI
  suggestions through server props, making transient proposals look like committed state and coupling a
  slow billable call to a full page render. The mutation line exists precisely to keep these apart.
- **A `fetch` for the spawn** — would bypass Inertia's prop refresh, forcing the client to hand-patch the
  sidebar after a write and re-implement what `back()` gives for free; it also splits the write path from
  every other mutation in the app.
- **Assists that edit the note in place** (rewrite the body with the atomized version) — destroys the
  user's original wording, is hard to undo, and turns a proposal into an irreversible action. Spawning
  new notes keeps the origin intact and the graph honest about provenance.

## Consequences

The read rail's suggestions are deliberately not persisted; a reader who expects the ideas to survive a
refresh needs this note to understand they are ephemeral by design. Every new assist inherits the same
shape: read-only AI via background `fetch` into ephemeral state, writes via an Inertia visit that
`back()`s — and none of them may rewrite the origin note's title or body. If a future assist genuinely
needs to edit note content, that is a new decision that revisits this record, not a quiet exception.
