import { xsrfToken } from '@/lib/csrf';
import {
    discover as discoverRoute,
    search as searchRoute,
} from '@/routes/notes';
import type { DiscoverySuggestion, NoteLink } from '@/types';

const JSON_HEADERS = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
} as const;

/**
 * Background JSON lookup against /notes/search — the same endpoint the Livewire
 * app used. It is a plain background request (not an Inertia visit), so the page
 * is never swapped and props are untouched — exactly what architecture-inertia.md
 * reserves for a typeahead lookup.
 */
export async function searchNotes(
    query: string,
    excludeId?: number,
): Promise<NoteLink[]> {
    const url = searchRoute.url({ query: { query, exclude: excludeId } });

    const response = await fetch(url, {
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': xsrfToken() },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return [];
    }

    const data = (await response.json()) as { results?: NoteLink[] };

    return data.results ?? [];
}

/**
 * Fetches discovery suggestions for a note on demand — the same background-JSON
 * pattern as searchNotes, hitting /notes/{slug}/discover when the Find
 * connections modal opens (not a show-page prop, since discovery is explicit).
 */
export async function discoverNotes(
    noteSlug: string,
): Promise<DiscoverySuggestion[]> {
    const response = await fetch(discoverRoute.url(noteSlug), {
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': xsrfToken() },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return [];
    }

    const data = (await response.json()) as {
        suggestions?: DiscoverySuggestion[];
    };

    return data.suggestions ?? [];
}
