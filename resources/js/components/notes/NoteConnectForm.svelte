<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ConnectionController from '@/actions/App/Http/Controllers/Note/ConnectionController';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { searchNotes } from '@/lib/note-search';
    import { untrack } from 'svelte';
    import type { Note, NoteLink, RelationshipGroup } from '@/types';

    let {
        note,
        relationshipOptions,
        onDone,
        initialTarget = null,
    }: {
        note: Note;
        relationshipOptions: RelationshipGroup[];
        onDone: () => void;
        initialTarget?: NoteLink | null;
    } = $props();

    let query = $state('');
    let results = $state<NoteLink[]>([]);
    let target = $state<NoteLink | null>(untrack(() => initialTarget));
    let relationship = $state('');
    let rationale = $state('');
    let processing = $state(false);

    async function runSearch(value: string) {
        query = value;
        target = null;

        if (value.trim().length === 0) {
            results = [];

            return;
        }

        results = await searchNotes(value, note.id);
    }

    function submit() {
        if (!target || !relationship) {
            return;
        }

        processing = true;
        router.post(
            ConnectionController.store.url(note.slug),
            {
                target_note_id: target.id,
                relationship,
                rationale: rationale || null,
            },
            {
                only: ['connections', 'incomingConnections'],
                preserveScroll: true,
                onFinish: () => {
                    processing = false;
                },
                onSuccess: () => onDone(),
            },
        );
    }
</script>

<div class="flex flex-col gap-2 rounded-md border p-3" data-test="connect-form">
    {#if target}
        <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-medium" data-test="connect-target">{target.title}</span>
            <button
                type="button"
                class="text-xs text-muted-foreground hover:underline"
                onclick={() => (target = null)}
                data-test="connect-clear-target"
            >
                change
            </button>
        </div>
    {:else}
        <div class="flex flex-col gap-1">
            <Input
                bind:value={query}
                oninput={(e) => runSearch((e.currentTarget as HTMLInputElement).value)}
                placeholder="Find a note…"
                aria-label="Find a note to connect"
                data-test="connect-search"
                class="h-8"
            />
            {#if results.length > 0}
                <ul class="flex flex-col gap-0.5" data-test="connect-results">
                    {#each results as result (result.id)}
                        <li>
                            <button
                                type="button"
                                class="w-full rounded px-2 py-1 text-left text-sm hover:bg-accent"
                                onclick={() => {
                                    target = result;
                                    results = [];
                                }}
                                data-test="connect-result"
                            >
                                {result.title}
                            </button>
                        </li>
                    {/each}
                </ul>
            {/if}
        </div>
    {/if}

    <select
        bind:value={relationship}
        aria-label="Relationship"
        data-test="connect-relationship"
        class="h-8 w-full rounded-md border bg-transparent px-2 text-sm"
    >
        <option value="">Relationship…</option>
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
        data-test="connect-rationale"
        class="h-8"
    />

    <div class="flex gap-2">
        <Button
            type="button"
            size="sm"
            onclick={submit}
            disabled={processing || !target || !relationship}
            data-test="connect-save"
        >
            Connect
        </Button>
        <Button type="button" size="sm" variant="ghost" onclick={onDone} data-test="connect-cancel">
            Cancel
        </Button>
    </div>
</div>
