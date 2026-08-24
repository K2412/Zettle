import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { NoteConnectForm } from '@/components/notes/note-connect-form';
import { NoteConnectionRow } from '@/components/notes/note-connection-row';
import { show } from '@/routes/notes';
import type { Connection, Note, RelationshipGroup, Tag } from '@/types';

type Props = {
    note: Note & { tags: Tag[] };
    connections: Connection[];
    incomingConnections: Connection[];
    relationshipOptions: RelationshipGroup[];
};

export function NoteConnectionsPanel({
    note,
    connections,
    incomingConnections,
    relationshipOptions,
}: Props) {
    const [connecting, setConnecting] = useState(false);

    // Group outgoing by their forward label so the panel is scannable.
    const outgoingByLabel = new Map<string, Connection[]>();

    for (const connection of connections) {
        const group = outgoingByLabel.get(connection.label) ?? [];
        group.push(connection);
        outgoingByLabel.set(connection.label, group);
    }

    const hasAny = connections.length > 0 || incomingConnections.length > 0;

    return (
        <section
            className="flex flex-col gap-3 border-t pt-4"
            data-test="connections-panel"
            aria-label="Connections"
        >
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-semibold text-muted-foreground">Connections</h2>
                {!connecting && (
                    <button
                        type="button"
                        className="text-xs text-muted-foreground hover:text-foreground hover:underline"
                        onClick={() => setConnecting(true)}
                        data-test="connect-toggle"
                    >
                        + Connect
                    </button>
                )}
            </div>

            {connecting && (
                <NoteConnectForm
                    note={note}
                    relationshipOptions={relationshipOptions}
                    onDone={() => setConnecting(false)}
                />
            )}

            {!hasAny && !connecting && (
                <span className="text-xs text-muted-foreground" data-test="connections-empty">
                    No connections yet.
                </span>
            )}

            {connections.length > 0 && (
                <div className="flex flex-col gap-3" data-test="outgoing-connections">
                    {[...outgoingByLabel.entries()].map(([label, rows]) => (
                        <div key={label} className="flex flex-col gap-1" data-test="connection-group">
                            <h3 className="text-xs font-medium text-foreground" data-test="connection-group-label">
                                {label}
                            </h3>
                            <ul className="flex flex-col gap-1.5">
                                {rows.map((connection) => (
                                    <NoteConnectionRow
                                        key={connection.id}
                                        note={note}
                                        connection={connection}
                                        relationshipOptions={relationshipOptions}
                                    />
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            )}

            {incomingConnections.length > 0 && (
                <div className="flex flex-col gap-1" data-test="incoming-connections">
                    <h3 className="text-xs font-medium text-muted-foreground">Incoming</h3>
                    <ul className="flex flex-col gap-1.5">
                        {incomingConnections.map((connection) => (
                            <li
                                key={connection.id}
                                className="flex flex-col gap-0.5"
                                data-test="incoming-connection-row"
                            >
                                <span className="text-xs text-muted-foreground" data-test="inverse-label">
                                    {connection.label}
                                </span>
                                <Link
                                    href={show(connection.note.slug)}
                                    className="text-sm hover:underline"
                                    data-test="incoming-connection-link"
                                >
                                    {connection.note.title}
                                </Link>
                                {connection.rationale && (
                                    <span className="text-xs text-muted-foreground">
                                        {connection.rationale}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </section>
    );
}
