<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import ConnectionController from '@/actions/App/Http/Controllers/Note/ConnectionController';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { show } from '@/routes/notes';
    import { untrack } from 'svelte';
    import type { Connection, Note, RelationshipGroup } from '@/types';

    let {
        note,
        connection,
        relationshipOptions,
    }: {
        note: Note;
        connection: Connection;
        relationshipOptions: RelationshipGroup[];
    } = $props();

    let editing = $state(false);
    let relationship = $state(untrack(() => connection.relationship));
    let rationale = $state(untrack(() => connection.rationale ?? ''));
    let processing = $state(false);

    const reload = { only: ['connections', 'incomingConnections'], preserveScroll: true };
    const routeArgs = $derived({ note: note.slug, connection: connection.id });

    function saveEdit() {
        processing = true;
        router.patch(
            ConnectionController.update.url(routeArgs),
            { relationship, rationale: rationale || null },
            {
                ...reload,
                onFinish: () => {
                    processing = false;
                },
                onSuccess: () => {
                    editing = false;
                },
            },
        );
    }

    function remove() {
        router.delete(ConnectionController.destroy.url(routeArgs), reload);
    }
</script>

{#if editing}
    <li class="flex flex-col gap-2 rounded-md border p-2" data-test="connection-edit">
        <select
            bind:value={relationship}
            aria-label="Relationship"
            data-test="edit-relationship"
            class="h-8 w-full rounded-md border bg-transparent px-2 text-sm"
        >
            {#each relationshipOptions as group (group.group)}
                <optgroup label={group.group}>
                    {#each group.options as option (option.value)}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </optgroup>
            {/each}
        </select>
        <Input
            bind:value={rationale}
            placeholder="Why? (optional)"
            aria-label="Rationale"
            data-test="edit-rationale"
            class="h-8"
        />
        <div class="flex gap-2">
            <Button type="button" size="sm" onclick={saveEdit} disabled={processing} data-test="edit-save">
                Save
            </Button>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                onclick={() => (editing = false)}
                data-test="edit-cancel"
            >
                Cancel
            </Button>
        </div>
    </li>
{:else}
    <li class="flex items-start justify-between gap-2" data-test="connection-row">
        <div class="flex flex-col gap-0.5">
            <Link href={show(connection.note.slug)} class="text-sm hover:underline" data-test="connection-link">
                {connection.note.title}
            </Link>
            {#if connection.rationale}
                <span class="text-xs text-muted-foreground">{connection.rationale}</span>
            {/if}
        </div>
        <div class="flex shrink-0 gap-1.5">
            <button
                type="button"
                class="text-xs text-muted-foreground hover:text-foreground hover:underline"
                onclick={() => (editing = true)}
                data-test="edit-connection"
            >
                edit
            </button>
            <button
                type="button"
                class="text-xs text-muted-foreground hover:text-destructive hover:underline"
                onclick={remove}
                aria-label={`Remove connection to ${connection.note.title}`}
                data-test="remove-connection"
            >
                remove
            </button>
        </div>
    </li>
{/if}
