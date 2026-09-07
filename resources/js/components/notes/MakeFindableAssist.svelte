<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import MakeFindableController from '@/actions/App/Http/Controllers/Note/MakeFindableController';
    import { Button } from '@/components/ui/button';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<div class="flex flex-col gap-3" data-test="make-findable-assist">
    <p class="text-sm text-muted-foreground">
        Suggest tags and a discovery hint so this note can be found again. Nothing is applied until
        you accept it.
    </p>

    <Button
        type="button"
        size="sm"
        variant="outline"
        onclick={panel.runMakeFindable}
        disabled={panel.makeFindableState === 'loading'}
        data-test="make-findable-run"
    >
        {panel.makeFindableState === 'loading' ? 'Finding…' : 'Suggest tags and hint'}
    </Button>

    {#if panel.makeFindableState === 'loading'}
        <div class="h-24 animate-pulse rounded-md bg-muted" data-test="make-findable-loading"></div>
    {/if}

    {#if panel.makeFindableState === 'ready' && panel.makeFindable === null}
        <p class="text-sm text-muted-foreground" data-test="make-findable-empty">
            No suggestions came back. Try again in a moment.
        </p>
    {/if}

    {#if panel.makeFindableState === 'ready' && panel.makeFindable !== null}
        <div class="flex flex-col gap-3 rounded-xl border p-4" data-test="make-findable-result">
            {#if panel.makeFindable.retrieval_contexts.length > 0}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-medium text-muted-foreground">Retrieval contexts</span>
                    <ul class="flex flex-col gap-1.5">
                        {#each panel.makeFindable.retrieval_contexts as context (context)}
                            <li
                                class="flex items-start justify-between gap-2 rounded-md border p-2"
                                data-test="make-findable-context"
                            >
                                <span class="text-sm">{context}</span>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick={() => navigator.clipboard.writeText(context)}
                                >
                                    Copy
                                </Button>
                            </li>
                        {/each}
                    </ul>
                </div>
            {/if}

            {#if panel.makeFindable.tags.length > 0}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-medium text-muted-foreground">Tags</span>
                    <ul class="flex flex-wrap gap-2">
                        {#each panel.makeFindable.tags as tag (tag)}
                            <li data-test="make-findable-tag">
                                <Form
                                    {...MakeFindableController.attachTag.form(note.slug)}
                                    options={{ only: ['note', 'availableTags'], preserveScroll: true }}
                                    resetOnSuccess
                                >
                                    {#snippet children({ processing })}
                                        <input type="hidden" name="name" value={tag} />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="outline"
                                            disabled={processing}
                                            data-test="make-findable-attach-tag"
                                        >
                                            + {tag}
                                        </Button>
                                    {/snippet}
                                </Form>
                            </li>
                        {/each}
                    </ul>
                </div>
            {/if}

            {#if panel.makeFindable.discovery_hint}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-medium text-muted-foreground">Discovery hint</span>
                    <p class="text-sm text-muted-foreground" data-test="make-findable-hint">
                        {panel.makeFindable.discovery_hint}
                    </p>
                    <Form
                        {...MakeFindableController.setHint.form(note.slug)}
                        options={{ only: ['note'], preserveScroll: true }}
                        resetOnSuccess
                    >
                        {#snippet children({ processing })}
                            <input
                                type="hidden"
                                name="discovery_hint"
                                value={panel.makeFindable?.discovery_hint ?? ''}
                            />
                            <Button type="submit" size="sm" disabled={processing} data-test="make-findable-set-hint">
                                Set discovery hint
                            </Button>
                        {/snippet}
                    </Form>
                </div>
            {/if}
        </div>
    {/if}
</div>
