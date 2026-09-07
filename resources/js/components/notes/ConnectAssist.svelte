<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ConnectController from '@/actions/App/Http/Controllers/Note/ConnectController';
    import { Button } from '@/components/ui/button';
    import { humanize } from '@/lib/humanize';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<div class="flex flex-col gap-3" data-test="connect-assist">
    <p class="text-sm text-muted-foreground">
        Suggest typed connections from this note. Each suggestion is yours to accept or ignore.
    </p>

    <Button
        type="button"
        size="sm"
        variant="outline"
        onclick={panel.runConnect}
        disabled={panel.connectState === 'loading'}
        data-test="connect-assist-run"
    >
        {panel.connectState === 'loading' ? 'Finding…' : 'Suggest connections'}
    </Button>

    {#if panel.connectState === 'loading'}
        <ul class="flex flex-col gap-2" data-test="connect-assist-loading">
            {#each [0, 1, 2] as i (i)}
                <li class="h-12 animate-pulse rounded-md bg-muted"></li>
            {/each}
        </ul>
    {/if}

    {#if panel.connectState === 'ready' && panel.candidates.length === 0}
        <p class="text-sm text-muted-foreground" data-test="connect-assist-empty">
            No connection suggestions yet.
        </p>
    {/if}

    {#if panel.connectState === 'ready' && panel.candidates.length > 0}
        <ul class="flex flex-col gap-3" data-test="connect-assist-candidates">
            {#each panel.candidates as candidate (candidate.note_id)}
                <li class="flex flex-col gap-2 rounded-xl border p-3" data-test="connect-assist-candidate">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium">{candidate.title}</span>
                        <span class="text-xs text-muted-foreground">
                            {humanize(candidate.relationship)}
                            {#if candidate.rationale}
                                — {candidate.rationale}
                            {/if}
                        </span>
                    </div>
                    <Form
                        {...ConnectController.link.form(note.slug)}
                        options={{ only: ['connections', 'incomingConnections'], preserveScroll: true }}
                        resetOnSuccess
                    >
                        {#snippet children({ processing })}
                            <input type="hidden" name="target_note_id" value={candidate.note_id} />
                            <input type="hidden" name="relationship" value={candidate.relationship} />
                            <input type="hidden" name="rationale" value={candidate.rationale ?? ''} />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                                data-test="connect-assist-link"
                            >
                                Create connection
                            </Button>
                        {/snippet}
                    </Form>
                </li>
            {/each}
        </ul>
    {/if}
</div>
