# Notes-core: markdown editor and search backend

Context: porting the Livewire `zettle` notes feature to React + Inertia. Two choices shape the
slice and everything built on it.

## Editor — CodeMirror 6 (markdown source), not TipTap or a plain textarea

The server derives note connections by parsing `[[wikilinks]]` out of the stored markdown `body`
(`NoteService::syncLinksFromBody`), and the Connections and graph slices build on that. So the note
`body` must stay markdown. We use **CodeMirror 6** as a markdown *source* editor (syntax highlighting
+ a `[[ ]]` autocomplete extension backed by the existing `/notes/search` endpoint). It is what
Obsidian uses, keeps `body` as markdown so server-side parsing is untouched, and turns the wikilink
autocomplete into a first-class CM source rather than a hand-rolled caret hook. Loaded lazily so the
index page doesn't pay for it.

Rejected: **TipTap / ProseMirror WYSIWYG** — would need lossy markdown round-tripping, a custom node +
serializer for `[[wikilinks]]`, and would turn an editor swap into a backend-contract change. Rejected:
**plain `<textarea>`** — faithful but a poor editing surface for a Zettelkasten.

A react-markdown **Preview tab** stays for this slice; inline *live-preview* (rendering markdown inside
CodeMirror) is deferred as later polish.

## Search — Laravel Scout with the `database` driver, Meilisearch deferred

The source uses Scout + Meilisearch; the target repo has neither installed. We add **`laravel/scout`
only** and run the **`database` driver** (no external Meilisearch service to operate for a local-first
app). `NoteService` keeps the Scout API unchanged, so swapping in Meilisearch later is a config change,
not a rewrite.

Rejected: **Scout + Meilisearch now** — best relevance but an external service to run for a single-user
local app; deferred to the search/embeddings slice where it earns its place. Rejected: **plain SQL
`LIKE` (no Scout)** — fewer deps but diverges from the source and drops the seam the embeddings slice
needs.
