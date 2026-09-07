<script lang="ts">
    import NoteConnectForm from '@/components/notes/NoteConnectForm.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { discoverNotes } from '@/lib/note-search';
    import type {
        DiscoverySuggestion,
        Note,
        NoteLink,
        RelationshipGroup,
        Tag,
    } from '@/types';

    let {
        note,
        relationshipOptions,
    }: {
        note: Note & { tags: Tag[] };
        relationshipOptions: RelationshipGroup[];
    } = $props();

    let open = $state(false);
    let loadState = $state<'idle' | 'loading' | 'ready'>('idle');
    let suggestions = $state<DiscoverySuggestion[]>([]);
    let target = $state<NoteLink | null>(null);

    async function load(nextOpen: boolean) {
        open = nextOpen;

        if (!nextOpen) {
            target = null;
            loadState = 'idle';

            return;
        }

        loadState = 'loading';
        suggestions = await discoverNotes(note.slug);
        loadState = 'ready';
    }
</script>

<Dialog bind:open onOpenChange={load}>
    <DialogTrigger asChild>
        {#snippet children(props)}
            <Button type="button" variant="outline" size="sm" data-test="find-connections" {...props}>
                Find connections
            </Button>
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <div data-test="discovery-modal">
            <DialogTitle>Find connections</DialogTitle>
            <DialogDescription>
                Notes related to this one by meaning. Pick one to connect.
            </DialogDescription>

            {#if target}
                <NoteConnectForm
                    {note}
                    {relationshipOptions}
                    initialTarget={target}
                    onDone={() => load(false)}
                />
            {:else if loadState === 'loading'}
                <ul class="flex flex-col gap-2" data-test="discovery-loading">
                    {#each [0, 1, 2] as i (i)}
                        <li class="h-12 animate-pulse rounded-md bg-muted"></li>
                    {/each}
                </ul>
            {:else if suggestions.length === 0}
                <p class="text-sm text-muted-foreground" data-test="discovery-empty">
                    No related notes yet. Add more notes to surface connections.
                </p>
            {:else}
                <ul class="flex flex-col gap-1" data-test="discovery-suggestions">
                    {#each suggestions as suggestion (suggestion.id)}
                        <li>
                            <button
                                type="button"
                                class="flex w-full flex-col gap-0.5 rounded-md px-3 py-2 text-left hover:bg-accent"
                                onclick={() => (target = suggestion)}
                                data-test="discovery-suggestion"
                            >
                                <span class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium">{suggestion.title}</span>
                                    <span
                                        class="text-xs text-muted-foreground tabular-nums"
                                        data-test="discovery-similarity"
                                    >
                                        {Math.round(suggestion.similarity * 100)}%
                                    </span>
                                </span>
                                {#if suggestion.snippet}
                                    <span class="line-clamp-1 text-xs text-muted-foreground">
                                        {suggestion.snippet}
                                    </span>
                                {/if}
                            </button>
                        </li>
                    {/each}
                </ul>
            {/if}
        </div>
    </DialogContent>
</Dialog>
