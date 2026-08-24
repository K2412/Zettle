import { Link, router } from '@inertiajs/react';
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
import { show } from '@/routes/notes';
import type { Connection, Note, RelationshipGroup } from '@/types';

const RELOAD = { only: ['connections', 'incomingConnections'], preserveScroll: true };

type Props = {
    note: Note;
    connection: Connection;
    relationshipOptions: RelationshipGroup[];
};

/**
 * One OUTGOING (authored) connection row: navigates to the target, and offers
 * edit-in-place + remove. Incoming rows are the computed inverse and get neither.
 */
export function NoteConnectionRow({ note, connection, relationshipOptions }: Props) {
    const [editing, setEditing] = useState(false);
    const [relationship, setRelationship] = useState(connection.relationship);
    const [rationale, setRationale] = useState(connection.rationale ?? '');
    const [processing, setProcessing] = useState(false);

    const routeArgs = { note: note.slug, connection: connection.id };

    const saveEdit = () => {
        setProcessing(true);
        router.patch(
            ConnectionController.update.url(routeArgs),
            { relationship, rationale: rationale || null },
            {
                ...RELOAD,
                onFinish: () => setProcessing(false),
                onSuccess: () => setEditing(false),
            },
        );
    };

    const remove = () => {
        router.delete(ConnectionController.destroy.url(routeArgs), RELOAD);
    };

    if (editing) {
        return (
            <li className="flex flex-col gap-2 rounded-md border p-2" data-test="connection-edit">
                <Select value={relationship} onValueChange={setRelationship}>
                    <SelectTrigger className="h-8" aria-label="Relationship" data-test="edit-relationship">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {relationshipOptions.map((group) => (
                            <SelectGroup key={group.group}>
                                <SelectLabel>{group.group}</SelectLabel>
                                {group.options.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
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
                    data-test="edit-rationale"
                    className="h-8"
                />
                <div className="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        onClick={saveEdit}
                        disabled={processing}
                        data-test="edit-save"
                    >
                        Save
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() => setEditing(false)}
                        data-test="edit-cancel"
                    >
                        Cancel
                    </Button>
                </div>
            </li>
        );
    }

    return (
        <li className="flex items-start justify-between gap-2" data-test="connection-row">
            <div className="flex flex-col gap-0.5">
                <Link
                    href={show(connection.note.slug)}
                    className="text-sm hover:underline"
                    data-test="connection-link"
                >
                    {connection.note.title}
                </Link>
                {connection.rationale && (
                    <span className="text-xs text-muted-foreground">{connection.rationale}</span>
                )}
            </div>
            <div className="flex shrink-0 gap-1.5">
                <button
                    type="button"
                    className="text-xs text-muted-foreground hover:text-foreground hover:underline"
                    onClick={() => setEditing(true)}
                    data-test="edit-connection"
                >
                    edit
                </button>
                <button
                    type="button"
                    className="text-xs text-muted-foreground hover:text-destructive hover:underline"
                    onClick={remove}
                    aria-label={`Remove connection to ${connection.note.title}`}
                    data-test="remove-connection"
                >
                    remove
                </button>
            </div>
        </li>
    );
}
