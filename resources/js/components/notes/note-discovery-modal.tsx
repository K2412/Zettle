import { useState } from 'react';
import { NoteConnectForm } from '@/components/notes/note-connect-form';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { discoverNotes } from '@/lib/note-search';
import type {
    DiscoverySuggestion,
    Note,
    NoteLink,
    RelationshipGroup,
    Tag,
} from '@/types';

type Props = {
    note: Note & { tags: Tag[] };
    relationshipOptions: RelationshipGroup[];
};

type State = 'idle' | 'loading' | 'ready';

/**
 * Find connections: a modal that fetches discovery suggestions on demand when it
 * opens, ranks them by similarity, and turns a picked suggestion into a
 * pre-filled +Connect form (the user still chooses relationship + rationale).
 * Nothing is connected until that form is submitted.
 */
export function NoteDiscoveryModal({ note, relationshipOptions }: Props) {
    const [open, setOpen] = useState(false);
    const [state, setState] = useState<State>('idle');
    const [suggestions, setSuggestions] = useState<DiscoverySuggestion[]>([]);
    const [target, setTarget] = useState<NoteLink | null>(null);

    const load = async (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setTarget(null);
            setState('idle');

            return;
        }

        setState('loading');
        setSuggestions(await discoverNotes(note.slug));
        setState('ready');
    };

    return (
        <Dialog open={open} onOpenChange={load}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    data-test="find-connections"
                >
                    Find connections
                </Button>
            </DialogTrigger>
            <DialogContent data-test="discovery-modal">
                <DialogHeader>
                    <DialogTitle>Find connections</DialogTitle>
                    <DialogDescription>
                        Notes related to this one by meaning. Pick one to
                        connect.
                    </DialogDescription>
                </DialogHeader>

                {target ? (
                    <NoteConnectForm
                        key={target.id}
                        note={note}
                        relationshipOptions={relationshipOptions}
                        initialTarget={target}
                        onDone={() => load(false)}
                    />
                ) : (
                    <DiscoveryList
                        state={state}
                        suggestions={suggestions}
                        onPick={setTarget}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function DiscoveryList({
    state,
    suggestions,
    onPick,
}: {
    state: State;
    suggestions: DiscoverySuggestion[];
    onPick: (target: NoteLink) => void;
}) {
    if (state === 'loading') {
        return (
            <ul className="flex flex-col gap-2" data-test="discovery-loading">
                {[0, 1, 2].map((i) => (
                    <li
                        key={i}
                        className="h-12 animate-pulse rounded-md bg-muted"
                    />
                ))}
            </ul>
        );
    }

    if (suggestions.length === 0) {
        return (
            <p
                className="text-sm text-muted-foreground"
                data-test="discovery-empty"
            >
                No related notes yet. Add more notes to surface connections.
            </p>
        );
    }

    return (
        <ul className="flex flex-col gap-1" data-test="discovery-suggestions">
            {suggestions.map((suggestion) => (
                <li key={suggestion.id}>
                    <button
                        type="button"
                        className="flex w-full flex-col gap-0.5 rounded-md px-3 py-2 text-left hover:bg-accent"
                        onClick={() => onPick(suggestion)}
                        data-test="discovery-suggestion"
                    >
                        <span className="flex items-center justify-between gap-2">
                            <span className="text-sm font-medium">
                                {suggestion.title}
                            </span>
                            <span
                                className="text-xs text-muted-foreground tabular-nums"
                                data-test="discovery-similarity"
                            >
                                {Math.round(suggestion.similarity * 100)}%
                            </span>
                        </span>
                        {suggestion.snippet && (
                            <span className="line-clamp-1 text-xs text-muted-foreground">
                                {suggestion.snippet}
                            </span>
                        )}
                    </button>
                </li>
            ))}
        </ul>
    );
}
