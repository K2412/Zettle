import type { CompletionContext, CompletionResult } from '@codemirror/autocomplete';
import { searchNotes } from '@/lib/note-search';

/**
 * A CodeMirror completion source for `[[wikilinks]]`. When the text just before
 * the caret is an open `[[…` with no closing `]]`, it queries /notes/search and
 * offers matching note titles; picking one inserts `[[Title]]`. CodeMirror's
 * own autocomplete UI handles keyboard nav (↑/↓/↵/esc) and the empty state.
 */
export function wikilinkCompletionSource(currentNoteId?: number) {
    return async (context: CompletionContext): Promise<CompletionResult | null> => {
        // Match an unclosed "[[" and capture what has been typed after it.
        const match = context.matchBefore(/\[\[([^\]]*)$/);

        if (!match) {
            return null;
        }

        const query = match.text.slice(2);

        // Trigger explicitly on "[[" even with no query yet; otherwise require a
        // typed query so we don't fire on unrelated positions.
        if (!context.explicit && query.trim() === '') {
            return {
                from: match.from,
                options: [],
                filter: false,
            };
        }

        const results = await searchNotes(query, currentNoteId);

        return {
            from: match.from,
            filter: false,
            options: results.map((note) => ({
                label: note.title,
                apply: `[[${note.title}]]`,
                type: 'text',
            })),
        };
    };
}
