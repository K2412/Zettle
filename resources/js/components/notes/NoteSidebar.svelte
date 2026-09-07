<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AssistPanel from '@/components/notes/AssistPanel.svelte';
    import NoteConnectionsPanel from '@/components/notes/NoteConnectionsPanel.svelte';
    import NoteDiscoveryModal from '@/components/notes/NoteDiscoveryModal.svelte';
    import NoteLinksPanel from '@/components/notes/NoteLinksPanel.svelte';
    import NoteTagsPanel from '@/components/notes/NoteTagsPanel.svelte';
    import { index } from '@/routes/notes';
    import type {
        Connection,
        Note,
        NoteLink,
        RelationshipGroup,
        Tag,
    } from '@/types';

    let {
        note,
        availableTags,
        outgoingLinks,
        backlinks,
        connections,
        incomingConnections,
        relationshipOptions,
    }: {
        note: Note & { tags: Tag[] };
        availableTags: Tag[];
        outgoingLinks: NoteLink[];
        backlinks: NoteLink[];
        connections: Connection[];
        incomingConnections: Connection[];
        relationshipOptions: RelationshipGroup[];
    } = $props();
</script>

<aside class="flex w-full shrink-0 flex-col gap-6 lg:w-64" data-test="note-sidebar">
    <Link href={index()} class="text-sm text-muted-foreground hover:underline" data-test="all-notes-link">
        ← All notes
    </Link>

    <NoteTagsPanel {note} {availableTags} />

    <NoteLinksPanel {outgoingLinks} {backlinks} />

    <NoteConnectionsPanel {note} {connections} {incomingConnections} {relationshipOptions} />

    <NoteDiscoveryModal {note} {relationshipOptions} />

    <AssistPanel {note} />
</aside>
