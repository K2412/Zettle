import { Form } from '@inertiajs/react';
import { useState } from 'react';
import AtomizeController from '@/actions/App/Http/Controllers/Note/AtomizeController';
import { Button } from '@/components/ui/button';
import { fetchAtomizeIdeas } from '@/lib/note-assists';
import type { AtomizeIdea, Note } from '@/types';

type Props = {
    note: Note;
};

type State = 'idle' | 'loading' | 'ready';

/**
 * The Atomize assist, on the client, over the two rails. Rail 1 ("Find the
 * ideas") is a read-only background fetch that fills ephemeral idea state — the
 * note is never touched. Rail 2 ("Spawn notes") is an Inertia <Form> write of
 * the checked titles; on success the origin's incoming connections refresh for
 * free from the controller's back() redirect, and a toast surfaces via flash.
 */
export function AtomizeAssist({ note }: Props) {
    const [state, setState] = useState<State>('idle');
    const [ideas, setIdeas] = useState<AtomizeIdea[]>([]);
    const [selected, setSelected] = useState<Set<string>>(new Set());

    const find = async () => {
        setState('loading');
        setSelected(new Set());
        setIdeas(await fetchAtomizeIdeas(note.slug));
        setState('ready');
    };

    const toggle = (title: string) => {
        setSelected((current) => {
            const next = new Set(current);

            if (next.has(title)) {
                next.delete(title);
            } else {
                next.add(title);
            }

            return next;
        });
    };

    return (
        <div className="flex flex-col gap-3" data-test="atomize-assist">
            <p className="text-sm text-muted-foreground">
                This note may hold several distinct ideas — spawn each as its
                own permanent note.
            </p>

            <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={find}
                disabled={state === 'loading'}
                data-test="atomize-find"
            >
                {state === 'loading' ? 'Finding…' : 'Find the ideas'}
            </Button>

            {state === 'loading' ? (
                <ul className="flex flex-col gap-2" data-test="atomize-loading">
                    {[0, 1, 2].map((i) => (
                        <li
                            key={i}
                            className="h-12 animate-pulse rounded-md bg-muted"
                        />
                    ))}
                </ul>
            ) : null}

            {state === 'ready' && ideas.length === 0 ? (
                <p
                    className="text-sm text-muted-foreground"
                    data-test="atomize-empty"
                >
                    No distinct ideas found. This note may already hold a single
                    idea.
                </p>
            ) : null}

            {state === 'ready' && ideas.length > 0 ? (
                <Form
                    {...AtomizeController.spawn.form(note.slug)}
                    options={{
                        only: ['incomingConnections'],
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    onSuccess={() => {
                        setIdeas([]);
                        setSelected(new Set());
                        setState('idle');
                    }}
                    className="flex flex-col gap-3"
                    data-test="atomize-ideas"
                >
                    {({ processing }) => (
                        <>
                            <ul className="flex flex-col gap-1.5">
                                {ideas.map((idea) => (
                                    <li key={idea.title}>
                                        <label
                                            className="flex cursor-pointer gap-2 rounded-md px-2 py-1.5 hover:bg-accent"
                                            data-test="atomize-idea"
                                        >
                                            <input
                                                type="checkbox"
                                                name="titles[]"
                                                value={idea.title}
                                                checked={selected.has(
                                                    idea.title,
                                                )}
                                                onChange={() =>
                                                    toggle(idea.title)
                                                }
                                                className="mt-0.5"
                                            />
                                            <span className="flex flex-col gap-0.5">
                                                <span className="text-sm font-medium">
                                                    {idea.title}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {idea.rationale}
                                                </span>
                                            </span>
                                        </label>
                                    </li>
                                ))}
                            </ul>

                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing || selected.size === 0}
                                data-test="atomize-spawn"
                            >
                                Spawn notes
                            </Button>
                        </>
                    )}
                </Form>
            ) : null}
        </div>
    );
}
