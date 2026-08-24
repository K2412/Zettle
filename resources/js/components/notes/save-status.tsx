import { Check, CircleDashed, Loader2 } from 'lucide-react';
import type { SaveStatus } from '@/hooks/use-note-editor';

const LABELS: Record<SaveStatus, string> = {
    saved: 'Saved',
    saving: 'Saving…',
    unsaved: 'Unsaved changes',
};

export function SaveStatusIndicator({ status }: { status: SaveStatus }) {
    const Icon = status === 'saving' ? Loader2 : status === 'saved' ? Check : CircleDashed;

    return (
        <span
            className="inline-flex items-center gap-1.5 text-sm text-muted-foreground"
            data-test="save-status"
            data-status={status}
        >
            <Icon className={`size-4 ${status === 'saving' ? 'animate-spin' : ''}`} />
            {LABELS[status]}
        </span>
    );
}
