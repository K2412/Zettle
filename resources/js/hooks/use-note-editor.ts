import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { xsrfToken } from '@/lib/csrf';
import { update } from '@/routes/notes';
import type { Note } from '@/types';

export type SaveStatus = 'saved' | 'unsaved' | 'saving';

const AUTOSAVE_DELAY = 1500;

type Draft = { title: string; body: string };

/**
 * Persist the draft with a keepalive request that survives the page teardown of
 * an SPA navigation — used to flush a pending autosave on unmount without
 * competing with the Inertia visit that triggered the unmount. `keepalive`
 * keeps the request alive across the unload; the promise is awaited so the
 * request is dispatched before this frame yields.
 */
async function flushSave(slug: string, draft: Draft): Promise<void> {
    await fetch(update(slug).url, {
        method: 'PATCH',
        keepalive: true,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(draft),
    }).catch(() => {
        // Best-effort flush; the autosave will retry on the next mount if it fails.
    });
}

/**
 * Owns the editor's client-only state: the working draft, the save status, and
 * the debounced autosave. A paused keystroke PATCHes the note and pulls back
 * only the props saving can change (note, outgoingLinks, backlinks). A pending
 * save is flushed on unmount, and a nav guard warns when leaving unsaved.
 */
export function useNoteEditor(note: Note) {
    const [draft, setDraft] = useState<Draft>(() => ({
        title: note.title,
        body: note.body ?? '',
    }));
    const [status, setStatus] = useState<SaveStatus>('saved');

    // Refs keep the debounce timer and the latest draft reachable from the
    // unmount cleanup without re-subscribing effects on every keystroke.
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const draftRef = useRef(draft);
    // Marks an in-flight autosave so the flush handler doesn't treat our own
    // PATCH as a navigation away.
    const savingRef = useRef(false);

    // Keep the mutable draft reachable from timers and the flush handler without
    // rebuilding effects on every keystroke.
    useEffect(() => {
        draftRef.current = draft;
    }, [draft]);

    const save = useCallback(() => {
        if (timer.current) {
            clearTimeout(timer.current);
            timer.current = null;
        }

        setStatus('saving');
        savingRef.current = true;

        router.patch(update(note.slug).url, draftRef.current, {
            only: ['note', 'outgoingLinks', 'backlinks'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setStatus('saved'),
            onError: () => setStatus('unsaved'),
            onFinish: () => {
                savingRef.current = false;
            },
        });
    }, [note.slug]);

    const setField = useCallback((field: keyof Draft, value: string) => {
        setDraft((prev) => ({ ...prev, [field]: value }));
        setStatus('unsaved');
    }, []);

    // Debounce: whenever the draft changes and is unsaved, schedule a save.
    useEffect(() => {
        if (status !== 'unsaved') {
            return;
        }

        timer.current = setTimeout(save, AUTOSAVE_DELAY);

        return () => {
            if (timer.current) {
                clearTimeout(timer.current);
                timer.current = null;
            }
        };
    }, [draft.title, draft.body, status, save]);

    // Flush a pending save before an in-app navigation leaves the editor. The
    // `before` event fires while the page is still alive, so a plain fetch (not
    // an Inertia visit — that would cancel the navigation) reliably persists the
    // draft. The unmount cleanup is a keepalive fallback for teardown paths the
    // `before` event doesn't cover.
    useEffect(() => {
        const off = router.on('before', () => {
            if (savingRef.current) {
                return;
            }

            if (timer.current) {
                clearTimeout(timer.current);
                timer.current = null;
                setStatus('saving');
                void flushSave(note.slug, draftRef.current).then(() => setStatus('saved'));
            }
        });

        return () => {
            off();

            if (timer.current) {
                clearTimeout(timer.current);
                timer.current = null;
                void flushSave(note.slug, draftRef.current);
            }
        };
    }, [note.slug]);

    // Nav guard for a hard browser close (tab close / reload) with unsaved
    // changes: the native beforeunload prompt lets the user cancel. In-app
    // Inertia navigations are handled by the unmount flush above, which saves
    // without losing keystrokes.
    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent) => {
            if (status !== 'saved') {
                event.preventDefault();
                event.returnValue = '';
            }
        };

        window.addEventListener('beforeunload', beforeUnload);

        return () => {
            window.removeEventListener('beforeunload', beforeUnload);
        };
    }, [status]);

    return { draft, status, setField, save };
}
