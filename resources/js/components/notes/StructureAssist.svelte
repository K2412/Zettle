<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import StructureController from '@/actions/App/Http/Controllers/Note/StructureController';
    import { Button } from '@/components/ui/button';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<div class="flex flex-col gap-3" data-test="structure-assist">
    <p class="text-sm text-muted-foreground">
        Propose a structure note that organizes this one alongside related notes. Creating it does
        not rewrite this note.
    </p>

    <Button
        type="button"
        size="sm"
        variant="outline"
        onclick={panel.runStructure}
        disabled={panel.structureState === 'loading'}
        data-test="structure-run"
    >
        {panel.structureState === 'loading' ? 'Structuring…' : 'Map this cluster'}
    </Button>

    {#if panel.structureState === 'loading'}
        <div class="h-24 animate-pulse rounded-md bg-muted" data-test="structure-loading"></div>
    {/if}

    {#if panel.structureState === 'ready' && panel.structure === null}
        <p class="text-sm text-muted-foreground" data-test="structure-empty">
            No structure proposal came back.
        </p>
    {/if}

    {#if panel.structureState === 'ready' && panel.structure !== null}
        <div class="flex flex-col gap-3 rounded-xl border p-4" data-test="structure-result">
            {#if panel.structure.central_question}
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-medium text-muted-foreground">Central question</span>
                    <span class="text-sm font-medium" data-test="structure-question">
                        {panel.structure.central_question}
                    </span>
                </div>
            {/if}
            {#if panel.structure.scaffold}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-medium text-muted-foreground">Scaffold</span>
                    <pre
                        class="overflow-x-auto whitespace-pre-wrap rounded-md border bg-muted/40 p-3 text-sm"
                        data-test="structure-scaffold"
                    >{panel.structure.scaffold}</pre>
                    <div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onclick={() => navigator.clipboard.writeText(panel.structure?.scaffold ?? '')}
                        >
                            Copy scaffold
                        </Button>
                    </div>
                </div>
            {/if}
            {#if panel.structure.index_entry}
                <p class="text-xs text-muted-foreground" data-test="structure-index">
                    Index entry: {panel.structure.index_entry}
                </p>
            {/if}

            <Form {...StructureController.create.form(note.slug)} resetOnSuccess>
                {#snippet children({ processing })}
                    <input type="hidden" name="title" value={panel.structure?.central_question ?? ''} />
                    {#each panel.structure?.cluster_note_ids ?? [] as id (id)}
                        <input type="hidden" name="cluster_note_ids[]" value={id} />
                    {/each}
                    <Button type="submit" size="sm" disabled={processing} data-test="structure-create">
                        Create structure note
                    </Button>
                {/snippet}
            </Form>
        </div>
    {/if}
</div>
