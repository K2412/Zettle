import { router } from '@inertiajs/svelte';
import { xsrfToken } from '@/lib/csrf';
import { update } from '@/routes/notes';
import type { Note } from '@/types';

export type SaveStatus = 'saved' | 'unsaved' | 'saving';

const AUTOSAVE_DELAY = 1500;

type Draft = { title: string; body: string };

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

interface NoteDraft {
    title: string;
    body: string;
    status: SaveStatus;
    setField: (field: keyof Draft, value: string) => void;
    save: () => void;
    start: () => () => void;
}

export class NoteEditorState implements NoteDraft {
    title = $state('');
    body = $state('');
    status = $state<SaveStatus>('saved');

    private slug: string;
    private timer: ReturnType<typeof setTimeout> | null = null;
    private saving = false;

    constructor(note: Note) {
        this.slug = note.slug;
        this.title = note.title;
        this.body = note.body ?? '';
    }

    private draft = (): Draft => ({ title: this.title, body: this.body });

    save = () => {
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }

        this.status = 'saving';
        this.saving = true;

        router.patch(update(this.slug).url, this.draft(), {
            only: ['note', 'outgoingLinks', 'backlinks'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                this.status = 'saved';
            },
            onError: () => {
                this.status = 'unsaved';
            },
            onFinish: () => {
                this.saving = false;
            },
        });
    };

    setField = (field: keyof Draft, value: string) => {
        this[field] = value;
        this.status = 'unsaved';

        if (this.timer) {
            clearTimeout(this.timer);
        }

        this.timer = setTimeout(this.save, AUTOSAVE_DELAY);
    };

    start = () => {
        const off = router.on('before', () => {
            if (this.saving) {
                return;
            }

            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
                this.status = 'saving';
                void flushSave(this.slug, this.draft()).then(() => {
                    this.status = 'saved';
                });
            }
        });

        const beforeUnload = (event: BeforeUnloadEvent) => {
            if (this.status !== 'saved') {
                event.preventDefault();
                event.returnValue = '';
            }
        };

        window.addEventListener('beforeunload', beforeUnload);

        return () => {
            off();
            window.removeEventListener('beforeunload', beforeUnload);

            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
                void flushSave(this.slug, this.draft());
            }
        };
    };
}
