# Research — typed-connection authoring UX references (Mobbin)

For the Connections slice: a manual, embedding-free way to link one note to another with a typed
`Relationship` (supports / contradicts / extends / …) + optional rationale, and to see/edit/remove
those typed connections. The source app has no such UI — this is a new feature — so these are
external analogues, mostly from issue-trackers and CRMs where "link two records with a labelled
relationship" is a solved pattern. Source: Mobbin (web). Each screen links to its Mobbin page.

## Target picker — how you choose the other note

- [incident.io — "Add related incident": typeahead + Link](https://mobbin.com/screens/e0d7052d-180c-4e04-9c22-6b70c9dbb906) — a compact modal, "Start typing or select from the dropdown", one Link button. The cleanest analogue for our picker; we already have the `/notes/search` typeahead from Notes-core to back it.
- [Plane — searchable item picker, "relates to", multi-select then "Add selected"](https://mobbin.com/screens/69cc8bb6-64bc-4e38-9770-9871a5dad1d2) — search box over records with checkboxes; shows the relation label ("relates to") alongside.
- [Jira — "Choose parent" typeahead in an Add dialog](https://mobbin.com/screens/0a1f5478-8549-40ba-a30e-7d0f386167e87) — minimal single-select target picker.

**Takeaway:** reuse the existing `[[`/`/notes/search` typeahead as the target picker. Single-select + a Link action (incident.io) fits a note-to-note edge better than multi-select.

## Relationship label — how you pick the type

- [Jira — "Linked work items: relates to KAN-8"](https://mobbin.com/screens/0a1f5478-8549-40ba-a30e-7d0f386167e87) — the linked item is shown under a named relationship ("relates to"); Jira's create-link flow picks the type from a dropdown (blocks / is blocked by / relates to / duplicates).
- [Zoho CRM — related-module picker with search](https://mobbin.com/screens/9deef204-413e-4746-9115-dcfe9caea8cf) — a searchable dropdown of relationship targets; relevant because our 16 `Relationship` types are too many for a flat list and may want search/grouping.

**Takeaway:** a relationship-type dropdown next to the target picker. Our enum has 16 types — likely expose a curated subset by default (a grill question). Consider grouping (e.g. "supports/contradicts/qualifies" vs "explains/depends-on/example-of").

## Directionality — one-way vs both-ways (the key model question)

- [Height — "Relationship: One-way / Both-ways" toggle](https://mobbin.com/screens/d03bb715-679d-4092-b46a-5dcd1ac57c51) — a relationship is explicitly configured as directed or symmetric. This is exactly our open question.
- [Fibery — "A Relation is a connection between two Databases visible from both ends"](https://mobbin.com/screens/c9817067-ab0e-43c8-96e0-954492da25cd) — relations are inherently bidirectional-visible; each end sees the other.
- Jira convention — some relations are **symmetric** ("relates to" shows "relates to" on both), others are **directed with a named inverse** ("blocks" ↔ "is blocked by").

**Takeaway (feeds the grill):** our `connections` table is a *directed* edge (`source_note_id`, `target_note_id`, `relationship`). Decision needed: when A "supports" B, what does B show? Options — (a) show the raw inbound edge ("← supported-by A" as a backlink with the type), (b) compute a named inverse per relationship (supports→"supported by", depends_on→"required by"), or (c) treat some types as symmetric (analogous_to, tension_with). Height/Jira suggest per-type inverse handling.

## Displaying typed connections on the note

- [Salesforce — the "Related" tab: sections grouped by relationship, each with a count and an Assign action](https://mobbin.com/screens/ae6cdc4a-5b08-4d00-980f-67926330e35c) — the reference for showing many typed relations on one record: grouped, counted, each group has an add affordance.
- [Jira — "Linked work items" section listing typed links inline on the detail page](https://mobbin.com/screens/0a1f5478-8549-40ba-a30e-7d0f386167e87) — lighter than tabs; a single section with typed rows.

**Takeaway:** extend the existing notes/show sidebar (which already has "Links to" + "Backlinks") with a typed-connections section — grouped by relationship label, each row = target note + a remove/edit affordance, plus a "+ Connect" button opening the picker. Salesforce's grouped-with-counts is the richer end; Jira's single inline section is the lighter end (a grill/taste call).

## Open questions surfaced (for the grill)

1. **Where does authoring live** — inline in the sidebar (a "+ Connect" that expands a picker), a modal (incident.io style, fits the stubbed connections-modal mount), or a dedicated panel?
2. **Directionality/inverse** — raw inbound edge vs computed named inverse vs symmetric-for-some-types (see above). Load-bearing for the data + display.
3. **Which relationships to expose** — all 16, or a curated default subset with the rest behind "more"? Grouped?
4. **Rationale** — required, optional, or inline-editable after the fact?
5. **Editing** — can you change a connection's type/rationale in place, or only remove + re-add?
6. **Relationship to `[[` mentions** — typed connections and wikilink `Mentions` coexist in the same table; the sidebar must distinguish "you wrote a link" (mentions) from "you asserted a relationship" (typed). Keep them visually/semantically separate.
