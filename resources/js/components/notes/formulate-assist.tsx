import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { evaluateDraft } from '@/lib/note-assists';
import { FORMULATE_TEMPLATES } from '@/lib/formulate-templates';
import type { Note } from '@/types';

type Props = {
    note: Note;
};

type EvalState = 'idle' | 'loading' | 'ready';

/**
 * The Formulate assist, on the client — the read-only assist that writes nothing
 * at all. The top rail is pure client state: picking a scaffold type shows its
 * empty markdown skeleton (from the local `FORMULATE_TEMPLATES` constant, no
 * round-trip) to copy into the note by hand. The bottom rail is a read-only
 * background fetch: it critiques the user's draft as prose and fills ephemeral
 * state — the note is never touched.
 */
export function FormulateAssist({ note }: Props) {
    const [selectedType, setSelectedType] = useState<string | null>(null);
    const [draft, setDraft] = useState('');
    const [critique, setCritique] = useState('');
    const [evalState, setEvalState] = useState<EvalState>('idle');

    // Derive the selected template during render — no need to store the body too.
    const selected =
        FORMULATE_TEMPLATES.find((template) => template.type === selectedType) ??
        null;

    const evaluate = async () => {
        setEvalState('loading');
        setCritique(await evaluateDraft(note.slug, draft));
        setEvalState('ready');
    };

    return (
        <div className="flex flex-col gap-4" data-test="formulate-assist">
            <p className="text-sm text-muted-foreground">
                Reach for a scaffold that fits the shape of the idea, or paste a
                draft below for a read-only critique.
            </p>

            <div className="flex flex-wrap gap-1.5" role="tablist">
                {FORMULATE_TEMPLATES.map((template) => {
                    const isActive = template.type === selectedType;

                    return (
                        <button
                            key={template.type}
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            onClick={() => setSelectedType(template.type)}
                            data-test={`formulate-template-${template.type}`}
                            className={
                                isActive
                                    ? 'rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground'
                                    : 'rounded-md border px-2 py-1 text-xs text-muted-foreground hover:text-foreground'
                            }
                        >
                            {template.label}
                        </button>
                    );
                })}
            </div>

            {selected !== null ? (
                <div className="flex flex-col gap-2" data-test="formulate-template">
                    <pre className="overflow-x-auto whitespace-pre-wrap rounded-xl border bg-muted/40 p-4 text-sm">
                        {selected.body}
                    </pre>
                    <div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() =>
                                navigator.clipboard.writeText(selected.body)
                            }
                            data-test="formulate-copy-template"
                        >
                            Copy template
                        </Button>
                    </div>
                </div>
            ) : null}

            <div className="flex flex-col gap-2">
                <label
                    className="text-xs font-medium text-muted-foreground"
                    htmlFor="formulate-draft"
                >
                    Your draft
                </label>
                <textarea
                    id="formulate-draft"
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    rows={6}
                    placeholder="Write or paste your draft to get a critique…"
                    data-test="formulate-draft"
                    className="rounded-xl border bg-transparent p-3 text-sm shadow-none focus-visible:ring-0"
                />
                <div>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={evaluate}
                        disabled={evalState === 'loading' || draft.trim() === ''}
                        data-test="formulate-evaluate"
                    >
                        {evalState === 'loading'
                            ? 'Evaluating…'
                            : 'Evaluate draft'}
                    </Button>
                </div>
            </div>

            {evalState === 'loading' ? (
                <div
                    className="h-20 animate-pulse rounded-md bg-muted"
                    data-test="formulate-evaluating"
                />
            ) : null}

            {evalState === 'ready' && critique !== '' ? (
                <div
                    className="flex flex-col gap-2 rounded-xl border p-4"
                    data-test="formulate-critique"
                >
                    <span className="text-xs font-medium text-muted-foreground">
                        Critique
                    </span>
                    <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                        {critique}
                    </p>
                    <div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() =>
                                navigator.clipboard.writeText(critique)
                            }
                            data-test="formulate-copy-critique"
                        >
                            Copy suggestions
                        </Button>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
