import AtomizeController from '@/actions/App/Http/Controllers/Note/AtomizeController';
import FormulateController from '@/actions/App/Http/Controllers/Note/FormulateController';
import TriageController from '@/actions/App/Http/Controllers/Note/TriageController';
import { xsrfToken } from '@/lib/csrf';
import type { AtomizeIdea, TriageResult } from '@/types';

const JSON_HEADERS = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
} as const;

/**
 * The read-only Atomize rail: a background POST to /notes/{slug}/assists/atomize
 * that runs the AI synthesis and returns candidate ideas as JSON. It is a plain
 * fetch (not an Inertia visit), so the page is never swapped and the note is
 * never touched — the ideas live in ephemeral React state until the user spawns.
 * POST (not GET) because AI generation is non-idempotent and billable.
 */
export async function fetchAtomizeIdeas(
    noteSlug: string,
): Promise<AtomizeIdea[]> {
    const response = await fetch(AtomizeController.run.url(noteSlug), {
        method: 'POST',
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': xsrfToken() },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return [];
    }

    const data = (await response.json()) as { ideas?: AtomizeIdea[] };

    return data.ideas ?? [];
}

/**
 * The read-only Triage rail: a background POST to /notes/{slug}/assists/triage
 * that classifies the note and returns a destination, a suggested type, and the
 * reasoning as JSON. Like Atomize it is a plain fetch, not an Inertia visit — the
 * page is never swapped and the note is never touched; the suggestion lives in
 * ephemeral React state until the user chooses to apply the type. POST (not GET)
 * because AI generation is non-idempotent and billable.
 */
export async function fetchTriage(noteSlug: string): Promise<TriageResult | null> {
    const response = await fetch(TriageController.run.url(noteSlug), {
        method: 'POST',
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': xsrfToken() },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return null;
    }

    return (await response.json()) as TriageResult;
}

/**
 * The read-only Formulate rail: a background POST to
 * /notes/{slug}/assists/formulate/evaluate that critiques the user's draft and
 * returns prose as JSON. A plain fetch, not an Inertia visit — Formulate writes
 * nothing at all, so the note is never touched and the critique lives only in
 * ephemeral React state. POST because AI generation is non-idempotent and billable.
 */
export async function evaluateDraft(noteSlug: string, draft: string): Promise<string> {
    const response = await fetch(FormulateController.evaluate.url(noteSlug), {
        method: 'POST',
        headers: {
            ...JSON_HEADERS,
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({ draft }),
    });

    if (!response.ok) {
        return '';
    }

    const data = (await response.json()) as { critique?: string };

    return data.critique ?? '';
}
