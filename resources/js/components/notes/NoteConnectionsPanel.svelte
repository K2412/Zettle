<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import NoteConnectForm from '@/components/notes/NoteConnectForm.svelte';
    import NoteConnectionRow from '@/components/notes/NoteConnectionRow.svelte';
    import { show } from '@/routes/notes';
    import type { Connection, Note, RelationshipGroup, Tag } from '@/types';

    let {
        note,
        connections,
        incomingConnections,
        relationshipOptions,
    }: {
        note: Note & { tags: Tag[] };
        connections: Connection[];
        incomingConnections: Connection[];
        relationshipOptions: RelationshipGroup[];
    } = $props();

    let connecting = $state(false);

    const outgoingByLabel = $derived.by(() => {
        const groups = new Map<string, Connection[]>();

        for (const connection of connections) {
            const group = groups.get(connection.label) ?? [];
            group.push(connection);
            groups.set(connection.label, group);
        }

        return [...groups.entries()];
    });

    const hasAny = $derived(connections.length > 0 || incomingConnections.length > 0);
</script>

<section class="flex flex-col gap-3 border-t pt-4" data-test="connections-panel" aria-label="Connections">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted-foreground">Connections</h2>
        {#if !connecting}
            <button
                type="button"
                class="text-xs text-muted-foreground hover:text-foreground hover:underline"
                onclick={() => (connecting = true)}
                data-test="connect-toggle"
            >
                + Connect
            </button>
        {/if}
    </div>

    {#if connecting}
        <NoteConnectForm {note} {relationshipOptions} onDone={() => (connecting = false)} />
    {/if}

    {#if !hasAny && !connecting}
        <span class="text-xs text-muted-foreground" data-test="connections-empty">No connections yet.</span>
    {/if}

    {#if connections.length > 0}
        <div class="flex flex-col gap-3" data-test="outgoing-connections">
            {#each outgoingByLabel as [label, rows] (label)}
                <div class="flex flex-col gap-1" data-test="connection-group">
                    <h3 class="text-xs font-medium text-foreground" data-test="connection-group-label">
                        {label}
                    </h3>
                    <ul class="flex flex-col gap-1.5">
                        {#each rows as connection (connection.id)}
                            <NoteConnectionRow {note} {connection} {relationshipOptions} />
                        {/each}
                    </ul>
                </div>
            {/each}
        </div>
    {/if}

    {#if incomingConnections.length > 0}
        <div class="flex flex-col gap-1" data-test="incoming-connections">
            <h3 class="text-xs font-medium text-muted-foreground">Incoming</h3>
            <ul class="flex flex-col gap-1.5">
                {#each incomingConnections as connection (connection.id)}
                    <li class="flex flex-col gap-0.5" data-test="incoming-connection-row">
                        <span class="text-xs text-muted-foreground" data-test="inverse-label">
                            {connection.label}
                        </span>
                        <Link
                            href={show(connection.note.slug)}
                            class="text-sm hover:underline"
                            data-test="incoming-connection-link"
                        >
                            {connection.note.title}
                        </Link>
                        {#if connection.rationale}
                            <span class="text-xs text-muted-foreground">{connection.rationale}</span>
                        {/if}
                    </li>
                {/each}
            </ul>
        </div>
    {/if}
</section>
