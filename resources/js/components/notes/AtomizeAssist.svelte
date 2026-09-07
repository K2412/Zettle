<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AtomizeController from '@/actions/App/Http/Controllers/Note/AtomizeController';
    import { Button } from '@/components/ui/button';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<div class="flex flex-col gap-3" data-test="atomize-assist">
    <p class="text-sm text-muted-foreground">
        This note may hold several distinct ideas — spawn each as its own permanent note.
    </p>

    <Button
        type="button"
        size="sm"
        variant="outline"
        onclick={panel.runAtomize}
        disabled={panel.atomizeState === 'loading'}
        data-test="atomize-find"
    >
        {panel.atomizeState === 'loading' ? 'Finding…' : 'Find the ideas'}
    </Button>

    {#if panel.atomizeState === 'loading'}
        <ul class="flex flex-col gap-2" data-test="atomize-loading">
            {#each [0, 1, 2] as i (i)}
                <li class="h-12 animate-pulse rounded-md bg-muted"></li>
            {/each}
        </ul>
    {/if}

    {#if panel.atomizeState === 'ready' && panel.ideas.length === 0}
        <p class="text-sm text-muted-foreground" data-test="atomize-empty">
            No distinct ideas found. This note may already hold a single idea.
        </p>
    {/if}

    {#if panel.atomizeState === 'ready' && panel.ideas.length > 0}
        <Form
            {...AtomizeController.spawn.form(note.slug)}
            options={{ only: ['incomingConnections'], preserveScroll: true }}
            resetOnSuccess
            onSuccess={panel.clearAtomize}
            class="flex flex-col gap-3"
            data-test="atomize-ideas"
        >
            {#snippet children({ processing })}
                <ul class="flex flex-col gap-1.5">
                    {#each panel.ideas as idea (idea.title)}
                        <li>
                            <label
                                class="flex cursor-pointer gap-2 rounded-md px-2 py-1.5 hover:bg-accent"
                                data-test="atomize-idea"
                            >
                                <input
                                    type="checkbox"
                                    name="titles[]"
                                    value={idea.title}
                                    checked={panel.selectedTitles.has(idea.title)}
                                    onchange={() => panel.toggleIdea(idea.title)}
                                    class="mt-0.5"
                                />
                                <span class="flex flex-col gap-0.5">
                                    <span class="text-sm font-medium">{idea.title}</span>
                                    <span class="text-xs text-muted-foreground">{idea.rationale}</span>
                                </span>
                            </label>
                        </li>
                    {/each}
                </ul>

                <Button
                    type="submit"
                    size="sm"
                    disabled={processing || panel.selectedTitles.size === 0}
                    data-test="atomize-spawn"
                >
                    Spawn notes
                </Button>
            {/snippet}
        </Form>
    {/if}
</div>
