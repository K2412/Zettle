# Typed connections: store once, compute the inverse

Context: the Connections slice lets a user assert a typed relationship from one note to another
(A `supports` B). Each note must show the relationship from its own side, so B should read
"supported by A" without the user entering anything twice.

## Decision

Store each authored connection as **one directed row** in the existing `connections` table
(`source_note_id`, `target_note_id`, `relationship`, optional `rationale`). The target note's view is
**computed**: a `Relationship::inverseLabel()` maps the forward type to a display-only phrase
(`supports` → "supported by", `depends_on` → "required by"). Relationships that read the same in both
directions — `contradicts`, `analogous_to`, `tension_with`, `distinguishes_from` — are marked
**symmetric** and render the same label each way. The `Relationship` enum is unchanged (still 18
values); inverse labels are display strings, not enum cases.

## Rejected

- **Reciprocal rows** (write a second row on the target) — doubles storage, invites the two rows
  drifting out of sync, and complicates delete/edit. A single row can't disagree with itself.
- **Inverse enum cases** (`supported_by`, `required_by`, …) — roughly doubles the enum, duplicates
  one concept across two names, and forces every write to keep the pair consistent.
- **No inverse** (show the raw inbound edge with its forward label) — simplest, but "supports" reads
  wrong on the receiving end and buries the direction.

## Consequences

Reversing this later (e.g. to reciprocal rows) is a data migration, and a reader who sees the target's
displayed label not matching any stored value needs this note to understand why. The `mentions` edges
that `[[wikilinks]]` create are excluded from this feature — authored connections never use the
`mentions` relationship, and the wikilink sync only ever reconciles `mentions` rows, so the two kinds
coexist without touching each other.
