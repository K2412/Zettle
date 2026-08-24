# Research — Notes-core UI references (Mobbin)

Visual references gathered for the Livewire→Inertia port of the **Notes core** slice
(index + show/editor). Source: Mobbin (web platform). Each screen links to its canonical
Mobbin page. Obsidian itself is not in Mobbin's library; the closest structural analogues
are captured below.

## Note list / index (list + search + tag filter + create)

- [Evernote — notes list with tag-filter dropdown + tag chips](https://mobbin.com/screens/2400dda3-acda-4fc5-9ae8-5be3fe31eb44) — three-pane layout (nav → note list → editor); a "Tags" filter menu and tag chips along the bottom of the editor. Closest match to our index tag-filter + list.
- [Zoom — "All notes" table with search and status tabs](https://mobbin.com/screens/93d86271-271a-43ee-a74f-07b6687a97c9) — top search field, sortable "Modified" column, tabbed scopes (All / Recent / Starred). Reference for a denser table-style list if we move off cards.
- [Frame — docs list with sort + label filter popover](https://mobbin.com/screens/51e68f9f-4866-4785-99f9-ed277b27f52e) — card list with a combined Sort/Filter popover (order by, filter by label, created-by-me). Reference for our tag filter as a popover instead of an inline chip row.
- [Twenty — notes list with a Relations column](https://mobbin.com/screens/dbb3526d-97d2-46f3-984e-b6515bab7629) — shows note→entity relations inline in the list; a lightweight way to surface connection counts on the index.

**Takeaways for our index:** the card-per-note list we have maps cleanly to these. Consider (a) moving the tag filter into a Sort/Filter popover (Frame) once tag count grows, and (b) surfacing a connection/backlink count per row (Twenty) in a later slice.

## Note editor / show (title + body, write/preview, save status)

- [Google AI Studio — Preview/Code tabs with "Unsaved changes → Save"](https://mobbin.com/screens/a5d0d15e-e84b-4c2b-be01-73d35206a099) — tabbed editor with an explicit dirty-state indicator and Save/Discard. Direct analogue of our Write/Preview tabs + `saved / saving / unsaved` status.
- [GitHub — markdown editor with a Preview tab](https://mobbin.com/screens/f392df44-d082-4ad9-b0ef-4615f2edbf35) — the canonical "edit ↔ preview" markdown toggle we're replicating.
- [Obvious — clean note editor with a floating formatting toolbar](https://mobbin.com/screens/de872062-f844-4074-a46e-9dbee6506a4a) — minimal, distraction-free body with formatting surfaced on demand. Reference for editor chrome/typography.
- [Lightfield — note detail with a metadata sidebar](https://mobbin.com/screens/ef271621-2ea1-4bc8-a501-dbfe9ac56ef8) — body + right-hand metadata panel (author, account, date). Mirrors our right sidebar (tags, links, backlinks).

**Takeaways for our editor:** keep the Write/Preview tabs; pair them with an unobtrusive save-status line (AI Studio). Right-sidebar metadata (Lightfield) matches our tags + links + backlinks aside.

## `[[wikilink]]` autocomplete (link-to-another-note popover)

- [WRITER — "Magic links": highlight a keyword, get suggested links + "Found N times in your doc"](https://mobbin.com/screens/d2cbc7e6-f49a-4868-93d3-358142145a2b) — the strongest analogue for our `[[` autocomplete: a compact suggestion panel with search, existing-doc matches, and a manual-URL fallback.
- [Confluence — "Insert list / link": search pages by keyword to insert](https://mobbin.com/screens/50bc3dfe-9f24-49c2-853e-536b1e0e24ab) — modal page-search for cross-linking; reference for the "no matches / searching…" states.
- [Notion-style page mention — inline command menu](https://mobbin.com/screens/bbcc94bb-f535-4dca-8532-56b135fce5c3) (StackAI) — inline dropdown of linkable destinations triggered mid-typing, with keyboard hint row (↑↓ select, ↵ open). Reference for keyboard affordances on our popover.

**Takeaways for our wikilink popover:** our current inline popover (triggered by `[[`, debounced search against `/notes/search`, click-to-insert) matches WRITER's pattern. Add: a keyboard-nav hint row (StackAI) and explicit "Searching… / No matches" states (Confluence) — both already present in the Livewire version, so preserve them.

## Backlinks / linked references

- [Evernote — "Backlinks (1)" + "Linked From: …" panel above the note body](https://mobbin.com/screens/ff3fc798-d894-4db5-8613-9015523b6bb3) — near-exact match for our outgoing-links + backlinks panels. Shows backlinks as a collapsible count at the top rather than a sidebar list — an alternative placement worth considering.

**Takeaways:** we currently render "Links to" + "Backlinks" as sidebar sections; Evernote's collapsible "Backlinks (N)" header is a compact alternative if the sidebar gets crowded once Connections lands.

## Open design questions surfaced (for the grill)

1. **List style** — keep card-per-note, or move toward a denser table (Zoom) as note volume grows?
2. **Tag filter placement** — inline chip row (current) vs. a Sort/Filter popover (Frame)?
3. **Backlinks placement** — right-sidebar sections (current) vs. a collapsible header count (Evernote)?
4. **Editor** — retain the raw-markdown textarea + Preview tab (GitHub/AI Studio), or move to a richer inline editor (Obvious)? The Livewire version is deliberately a markdown textarea; changing that is a scope decision.
5. **Wikilink popover keyboarding** — add ↑↓/↵ navigation (StackAI) on top of the existing click-to-insert.
