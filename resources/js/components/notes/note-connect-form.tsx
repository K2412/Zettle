import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConnectionController from '@/actions/App/Http/Controllers/Note/ConnectionController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { searchNotes } from '@/lib/note-search';
import type { Note, NoteLink, RelationshipGroup } from '@/types';

const RELOAD = {
    only: ['connections', 'incomingConnections'],
    preserveScroll: true,
};

type Props = {
    note: Note;
    relationshipOptions: RelationshipGroup[];
    onDone: () => void;
    initialTarget?: NoteLink | null;
};

export function NoteConnectForm({
    note,
    relationshipOptions,
    onDone,
    initialTarget = null,
}: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<NoteLink[]>([]);
    const [target, setTarget] = useState<NoteLink | null>(initialTarget);
    const [relationship, setRelationship] = useState('');
    const [rationale, setRationale] = useState('');
    const [processing, setProcessing] = useState(false);

    const runSearch = async (value: string) => {
        setQuery(value);
        setTarget(null);

        if (value.trim().length === 0) {
            setResults([]);

            return;
        }

        setResults(await searchNotes(value, note.id));
    };

    const submit = () => {
        if (!target || !relationship) {
            return;
        }

        setProcessing(true);
        router.post(
            ConnectionController.store.url(note.slug),
            {
                target_note_id: target.id,
                relationship,
                rationale: rationale || null,
            },
            {
                ...RELOAD,
                onFinish: () => setProcessing(false),
                onSuccess: () => onDone(),
            },
        );
    };

    return (
        <div
            className="flex flex-col gap-2 rounded-md border p-3"
            data-test="connect-form"
        >
            {target ? (
                <div className="flex items-center justify-between gap-2">
                    <span
                        className="text-sm font-medium"
                        data-test="connect-target"
                    >
                        {target.title}
                    </span>
                    <button
                        type="button"
                        className="text-xs text-muted-foreground hover:underline"
                        onClick={() => setTarget(null)}
                        data-test="connect-clear-target"
                    >
                        change
                    </button>
                </div>
            ) : (
                <div className="flex flex-col gap-1">
                    <Input
                        value={query}
                        onChange={(e) => runSearch(e.target.value)}
                        placeholder="Find a note…"
                        aria-label="Find a note to connect"
                        data-test="connect-search"
                        className="h-8"
                    />
                    {results.length > 0 && (
                        <ul
                            className="flex flex-col gap-0.5"
                            data-test="connect-results"
                        >
                            {results.map((result) => (
                                <li key={result.id}>
                                    <button
                                        type="button"
                                        className="w-full rounded px-2 py-1 text-left text-sm hover:bg-accent"
                                        onClick={() => {
                                            setTarget(result);
                                            setResults([]);
                                        }}
                                        data-test="connect-result"
                                    >
                                        {result.title}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            <Select value={relationship} onValueChange={setRelationship}>
                <SelectTrigger
                    className="h-8"
                    aria-label="Relationship"
                    data-test="connect-relationship"
                >
                    <SelectValue placeholder="Relationship…" />
                </SelectTrigger>
                <SelectContent>
                    {relationshipOptions.map((group) => (
                        <SelectGroup key={group.group}>
                            <SelectLabel>{group.group}</SelectLabel>
                            {group.options.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    ))}
                </SelectContent>
            </Select>

            <Input
                value={rationale}
                onChange={(e) => setRationale(e.target.value)}
                placeholder="Why? (optional)"
                aria-label="Rationale"
                data-test="connect-rationale"
                className="h-8"
            />

            <div className="flex gap-2">
                <Button
                    type="button"
                    size="sm"
                    onClick={submit}
                    disabled={processing || !target || !relationship}
                    data-test="connect-save"
                >
                    Connect
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    onClick={onDone}
                    data-test="connect-cancel"
                >
                    Cancel
                </Button>
            </div>
        </div>
    );
}
