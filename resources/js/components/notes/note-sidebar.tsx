import { Link } from '@inertiajs/react';
import { AssistPanel } from '@/components/notes/assist-panel';
import { NoteConnectionsPanel } from '@/components/notes/note-connections-panel';
import { NoteDiscoveryModal } from '@/components/notes/note-discovery-modal';
import { NoteLinksPanel } from '@/components/notes/note-links-panel';
import { NoteTagsPanel } from '@/components/notes/note-tags-panel';
import { index } from '@/routes/notes';
import type {
    Connection,
    Note,
    NoteLink,
    PhaseOption,
    RelationshipGroup,
    Tag,
} from '@/types';

type Props = {
    note: Note & { tags: Tag[] };
    availableTags: Tag[];
    outgoingLinks: NoteLink[];
    backlinks: NoteLink[];
    connections: Connection[];
    incomingConnections: Connection[];
    relationshipOptions: RelationshipGroup[];
    suggestedPhase: string;
    phases: PhaseOption[];
};

export function NoteSidebar({
    note,
    availableTags,
    outgoingLinks,
    backlinks,
    connections,
    incomingConnections,
    relationshipOptions,
    suggestedPhase,
    phases,
}: Props) {
    return (
        <aside
            className="flex w-full shrink-0 flex-col gap-6 lg:w-64"
            data-test="note-sidebar"
        >
            <Link
                href={index()}
                className="text-sm text-muted-foreground hover:underline"
                data-test="all-notes-link"
            >
                ← All notes
            </Link>

            <NoteTagsPanel note={note} availableTags={availableTags} />

            <NoteLinksPanel
                outgoingLinks={outgoingLinks}
                backlinks={backlinks}
            />

            <NoteConnectionsPanel
                note={note}
                connections={connections}
                incomingConnections={incomingConnections}
                relationshipOptions={relationshipOptions}
            />

            <NoteDiscoveryModal
                note={note}
                relationshipOptions={relationshipOptions}
            />

            <AssistPanel
                note={note}
                suggestedPhase={suggestedPhase}
                phases={phases}
            />
        </aside>
    );
}
