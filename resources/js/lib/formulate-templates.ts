/** One Formulate scaffold: a note-type keyword, its titlecased label, and the empty markdown skeleton. */
export type FormulateTemplate = {
    type: string;
    label: string;
    body: string;
};

/**
 * The eight Formulate scaffolds — empty, type-appropriate markdown skeletons the
 * user fills in themselves. Pure client-side constants: no AI, no note write, no
 * round-trip. Ported verbatim from the source `FormulateAssist::templates()`, so
 * the scaffolds a note gets are identical whether they came from server or client.
 */
export const FORMULATE_TEMPLATES: FormulateTemplate[] = [
    {
        type: 'permanent',
        label: 'Permanent',
        body: `# [The claim, stated as a title — a sentence, not a topic]

[Explain the one idea in full sentences. Define any term needed to understand it.]

## Because
[The reason, mechanism, argument, or evidence.]

## Boundary
[Scope condition, uncertainty, limitation, or what this does NOT claim.]

## Source
[Where it came from: source + location, an observation, or the notes it grew from.]

## Next
[Optional: a question, test, application, or missing evidence it points to.]`,
    },
    {
        type: 'argument',
        label: 'Argument',
        body: `# [The conclusion, stated as a claim]

## Premises
- [Premise 1]
- [Premise 2]

## Because
[How the premises support the conclusion.]

## Boundary
[Where the argument stops holding.]

## Source
[Provenance.]`,
    },
    {
        type: 'distinction',
        label: 'Distinction',
        body: `# [X is not Y — the distinction, stated as a claim]

## Distinction
[What separates the two, and why the line matters.]

## Because
[The reasoning behind the distinction.]

## Boundary
[Borderline cases; where the distinction blurs.]

## Source
[Provenance.]`,
    },
    {
        type: 'mechanism',
        label: 'Mechanism',
        body: `# [The mechanism, stated as a claim about how something works]

## Mechanism
[Step by step: what causes what, in order.]

## Because
[Why this mechanism holds.]

## Boundary
[Conditions under which the mechanism fails.]

## Source
[Provenance.]`,
    },
    {
        type: 'question',
        label: 'Question',
        body: `# [The open question, stated precisely]

## Why it matters
[What resolving this would change.]

## What would answer it
[Evidence, test, or argument that would settle it.]

## Boundary
[Scope of the question.]

## Source
[Where the question arose.]`,
    },
    {
        type: 'tension',
        label: 'Tension',
        body: `# [The tension between A and B, stated as a claim]

## Tension
[What pulls in each direction and why they conflict.]

## Because
[The source of the conflict.]

## Boundary
[Whether the tension is genuine or only apparent.]

## Source
[Provenance.]`,
    },
    {
        type: 'application',
        label: 'Application',
        body: `# [The application, stated as a claim about where an idea is used]

## Application
[The context, and how the idea is put to work there.]

## Because
[Why the idea transfers to this context.]

## Boundary
[Where the application breaks down.]

## Source
[Provenance.]`,
    },
    {
        type: 'counterexample',
        label: 'Counterexample',
        body: `# [The counterexample, stated as a claim against a general rule]

## Counterexample
[The case, and the rule it refutes or limits.]

## Because
[Why it counts as a counterexample.]

## Boundary
[What survives of the original rule.]

## Source
[Provenance.]`,
    },
];
