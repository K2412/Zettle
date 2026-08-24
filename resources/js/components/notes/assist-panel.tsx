import { useState } from 'react';
import { AtomizeAssist } from '@/components/notes/atomize-assist';
import type { Note, PhaseOption } from '@/types';

type Props = {
    note: Note;
    suggestedPhase: string;
    phases: PhaseOption[];
};

/**
 * The assist panel: one tab per playbook phase, with the suggested phase marked
 * and active on mount. Which phase is active is ephemeral client state — a tab
 * switch only swaps the shown child, it never touches or navigates the note.
 * Only Atomize is wired today; the rest show a quiet placeholder.
 */
export function AssistPanel({ note, suggestedPhase, phases }: Props) {
    const [activePhase, setActivePhase] = useState(suggestedPhase);

    return (
        <section
            className="flex flex-col gap-3 border-t pt-4"
            data-test="assist-panel"
            aria-label="Assists"
        >
            <h2 className="text-sm font-semibold text-muted-foreground">
                Assists
            </h2>

            <div className="flex flex-wrap gap-1.5" role="tablist">
                {phases.map((phase) => {
                    const isSuggested = phase.value === suggestedPhase;
                    const isActive = phase.value === activePhase;

                    return (
                        <button
                            key={phase.value}
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            onClick={() => setActivePhase(phase.value)}
                            data-test={`phase-tab-${phase.value}`}
                            className={
                                isActive
                                    ? 'rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground'
                                    : 'rounded-md border px-2 py-1 text-xs text-muted-foreground hover:text-foreground'
                            }
                        >
                            {phase.label}
                            {isSuggested ? (
                                <span className="ml-1 opacity-70">
                                    · suggested
                                </span>
                            ) : null}
                        </button>
                    );
                })}
            </div>

            <div data-test="assist-active">
                {activePhase === 'atomize' ? (
                    <AtomizeAssist note={note} />
                ) : (
                    <PhasePlaceholder />
                )}
            </div>
        </section>
    );
}

function PhasePlaceholder() {
    return (
        <div
            className="rounded-xl border border-dashed p-6 text-center text-muted-foreground"
            data-test="phase-placeholder"
        >
            <p className="font-medium">Nothing here yet.</p>
            <p className="text-sm">This assist is on the way.</p>
        </div>
    );
}
