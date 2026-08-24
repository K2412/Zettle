# Design patterns — house style

The settled taste of the `zettle-inertia` front end: the choices already made across the shipped
slices (notes-core, connections, search+embeddings), written down so later work matches them and a
taste-review has something to anchor against. This is a **taste reference, not a rulebook** — it
records *how this app already looks and reads*, so a new screen feels like the same hand wrote it.

Source of truth is the code; when this doc and the code disagree, the code wins and this doc is stale.
Representative files: `resources/js/pages/notes/index.tsx`, `resources/js/pages/notes/show.tsx`,
`resources/js/components/heading.tsx`.

## Page shell

- A page is a **centered single column**: `mx-auto flex w-full max-w-* flex-col gap-6 p-4`.
  - `max-w-3xl` for list/reading widths (notes index); `max-w-5xl` for wide/work surfaces (note editor).
  - Vertical rhythm is **`gap-6`** between major sections, `gap-2`/`gap-3` within a group.
- Every page opens with `<Head title="…" />` and declares its breadcrumb via the static
  `Page.layout = { breadcrumbs: [...] }` property — never prop-drilled into the layout.

## Headings & type

- There is a shared **`Heading`** component (`components/heading.tsx`): an `h2`,
  `text-xl font-semibold tracking-tight`, with an optional muted `text-sm` description under it. Prefer
  it for a section/page title over a hand-rolled `<h1>`/`<h2>`.
- Body meta / secondary text is **`text-sm text-muted-foreground`**. Use it for counts, hints, sublines.
- The note title on the editor is the one deliberately large field: `text-2xl font-semibold`.

## Color

- **Use semantic Tailwind tokens, not raw hex**, in markup: `text-muted-foreground`, `text-destructive`,
  `bg-primary text-primary-foreground`, `bg-accent`, `border`. This is what keeps dark mode and theming
  free.
- **User-data colors are the exception** — a tag's `color` is applied inline (`style={{ color }}`),
  because it's data, not theme. Same for anything drawn to a `<canvas>`, which can't read CSS tokens
  and must use concrete color values.
- The grey fallback for "no color" is `#6b7280`.

## Empty states

The house empty state (see notes index) — reach for this shape, don't reinvent it:

```tsx
<div className="rounded-xl border border-dashed p-10 text-center text-muted-foreground" data-test="empty-state">
    <p className="font-medium">No notes yet.</p>
    <p className="text-sm">Create your first spark above.</p>
</div>
```

Two lines: a **bold headline** stating the emptiness plainly, then a **muted `text-sm` sub-line** that
tells the user the one action that fills it. Dashed border, generous `p-10`, centered.

## Surfaces & borders

- Cards / bounded regions: `rounded-xl border`. Dashed border (`border-dashed`) signals "empty /
  placeholder"; solid border signals real content.
- Reuse **shadcn/ui primitives** (`components/ui/*`: Button, Input, Badge, Select, Card, Dialog, Tabs,
  Skeleton…) before writing new markup. Extend via `class-variance-authority` variants, not copy-paste.

## Icons

- **lucide-react**, imported by name (`import { Waypoints } from 'lucide-react'`). Nav items pair a
  short title with one icon. Pick an icon that reads as the *noun* of the screen at a glance.

## Copy & tone

- **Warm, plain, a little evocative — never corporate.** The app's metaphor is thinking, so the copy
  leans into it: a new note is "A new spark…", the notes empty state says "Create your first spark
  above." Microcopy is **sentence case**, ends without a period in short labels, uses periods in full
  sentences.
- Placeholders are inviting, not instructional: "Search notes…", "A new spark…", "Untitled".
- Prefer the domain word (see `CONTEXT.md`): *note*, *connection*, *mention*, *wikilink*, *tag* — not
  "link" (ambiguous) or "relation".

## Testing hooks

- Anything a browser test needs to find carries a **`data-test="kebab-name"`** attribute
  (`data-test="empty-state"`, `data-test="search-form"`). Add them as you build, named for the thing
  not the styling.

## React mechanics (taste-adjacent, enforced elsewhere)

- React Compiler is ON — no manual `useMemo`/`useCallback`/`memo`. Derive during render; keep
  transient/per-frame state in a `ref`. (Full rules live in `docs/architecture-inertia.md`.)
