import AtomizeController from '@/actions/App/Http/Controllers/Note/AtomizeController';
import { xsrfToken } from '@/lib/csrf';
import type { AtomizeIdea } from '@/types';

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
