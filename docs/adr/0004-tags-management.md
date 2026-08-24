# Tags become a managed, first-class surface

Context: tags were only ever attach/detach/create-on-a-note plus an index filter, in both the source
Livewire app and this port — there was no richer management to port, despite the migration brief
assuming one. This slice builds it net-new: a dedicated `/tags` page where a user renames, recolors,
deletes, and merges their tags. The `tags` table already carries a `color` (`VARCHAR(7)`, default
`#6b7280`) and a `unique(user_id, slug)` constraint, and `note_tag` cascades on delete.

## Decision

- **A new resourceful tag surface.** A thin `TagController` (`index`/`update`/`destroy`) at `tags.*`
  routes, plus a separate merge endpoint, sits alongside the existing note-scoped `NoteTagController`
  (which stays as-is for attach/detach on a note). A new **`TagPolicy`** owns the "you own this tag"
  gate for every management write, replacing the inline `abort_unless` used on the note-scoped routes.
- **Free-hex colors, no palette, no migration.** Color is chosen with a native `<input type="color">`
  and validated server-side with `/^#[0-9a-f]{6}$/i`, stored lowercased. `#rrggbb` is the only shape
  the existing `VARCHAR(7)` column holds, so the schema is untouched — the Form Request is the gate.
- **Rename re-slugs the tag.** The slug is the dedup key for note-tagging (`firstOrCreate` on
  `(user_id, slug)`), so a rename regenerates the slug to keep one name ↔ one tag. A rename that would
  collide with an existing tag's slug is **rejected** with a validation error, not silently merged.
- **Merge folds a source tag into a target.** `MergeTags` (a Laravel Action, in a transaction)
  `syncWithoutDetaching`s the target onto every note the source touched, then deletes the source and
  lets the `note_tag` FK cascade remove its old pivot rows. The **source is deleted; the target's name
  and color survive.**

## Rejected

- **Extending `NoteTagController`** for management verbs — those act on a tag itself, not a note's
  relationship to one; the URLs would need a note they don't have.
- **A preset palette** (the source's 8 random-assigned colors) — the user chose free hex for full
  control; the palette identity isn't load-bearing here.
- **Raw pivot remap on merge** (`UPDATE note_tag SET tag_id = target WHERE tag_id = source`) — a note
  carrying both tags violates `unique(note_id, tag_id)`; `syncWithoutDetaching` makes the duplicate a
  no-op instead.
- **Freezing the slug at creation** — decouples display name from dedup identity and breeds silent
  duplicate tags on re-tagging.
- **Auto-merging a colliding rename** — convenient but destructive by surprise (a tag vanishes without
  the user asking); merge is the explicit, named path for combining.

## Consequences

Tags now have write paths beyond a note's sidebar, so any future tag work authorizes through
`TagPolicy`. Recolors and renames are plain Inertia round-trips (no optimistic update) — simple and
consistent with the rest of the app; revisit only if a colour tweak feels slow. Merge is irreversible
and delete is irreversible (the cascade detaches from every note); both surface a confirm. A reader who
finds a tag's slug not matching `Str::slug(name)` should suspect a bug, since rename keeps them in
lockstep.
