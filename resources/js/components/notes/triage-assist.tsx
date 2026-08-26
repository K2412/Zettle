import { Form } from '@inertiajs/react';
import { useState } from 'react';
import TriageController from '@/actions/App/Http/Controllers/Note/TriageController';
import { Button } from '@/components/ui/button';
import { fetchTriage } from '@/lib/note-assists';
import type { Note, TriageResult } from '@/types';

type Props = {
    note: Note;
};

type State = 'idle' | 'loading' | 'ready';

/** Turn a raw enum value (`project_only`, `permanent`) into a readable label (`Project only`). */
function humanize(value: string): string {
    const spaced = value.replace(/_/g, ' ');

    return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}

/**
 * The Triage assist, on the client, over the two rails. Rail 1 ("Run triage") is
 * a read-only background fetch that fills ephemeral suggestion state — the note is
 * never touched. Rail 2 ("Set type to …") is an Inertia <Form> write of the one
 * accepted type; on success a toast surfaces via flash and the suggestion clears.
 */
export function TriageAssist({ note }: Props) {
    const [state, setState] = useState<State>('idle');
    const [result, setResult] = useState<TriageResult | null>(null);

    const run = async () => {
        setState('loading');
        setResult(await fetchTriage(note.slug));
        setState('ready');
    };

    return (
        <div className="flex flex-col gap-3" data-test="triage-assist">
            <p className="text-sm text-muted-foreground">
                Decide what should happen to this note — where it goes and what
                type it is — then set the type if the suggestion fits.
            </p>

            <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={run}
                disabled={state === 'loading'}
                data-test="triage-run"
            >
                {state === 'loading' ? 'Triaging…' : 'Run triage'}
            </Button>

            {state === 'loading' ? (
                <div
                    className="h-24 animate-pulse rounded-md bg-muted"
                    data-test="triage-loading"
                />
            ) : null}

            {state === 'ready' && result === null ? (
                <p
                    className="text-sm text-muted-foreground"
                    data-test="triage-empty"
                >
                    Triage came back empty. Try again in a moment.
                </p>
            ) : null}

            {state === 'ready' && result !== null ? (
                <div
                    className="flex flex-col gap-3 rounded-xl border p-4"
                    data-test="triage-result"
                >
                    <div className="flex flex-col gap-1">
                        <span className="text-xs font-medium text-muted-foreground">
                            Destination
                        </span>
                        <span className="text-sm font-medium">
                            {humanize(result.destination)}
                        </span>
                    </div>

                    <div className="flex flex-col gap-1">
                        <span className="text-xs font-medium text-muted-foreground">
                            Suggested type
                        </span>
                        <span className="text-sm font-medium">
                            {humanize(result.note_type)}
                        </span>
                    </div>

                    <div className="flex flex-col gap-1">
                        <span className="text-xs font-medium text-muted-foreground">
                            Reasoning
                        </span>
                        <p className="text-sm text-muted-foreground">
                            {result.reasoning}
                        </p>
                    </div>

                    <Form
                        {...TriageController.applyType.form(note.slug)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        onSuccess={() => {
                            setResult(null);
                            setState('idle');
                        }}
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="note_type"
                                    value={result.note_type}
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                    data-test="triage-apply"
                                >
                                    Set type to {humanize(result.note_type)}
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            ) : null}
        </div>
    );
}
