<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import TriageController from '@/actions/App/Http/Controllers/Note/TriageController';
    import { Button } from '@/components/ui/button';
    import { humanize } from '@/lib/humanize';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<div class="flex flex-col gap-3" data-test="triage-assist">
    <p class="text-sm text-muted-foreground">
        Decide what should happen to this note — where it goes and what type it is — then set the
        type if the suggestion fits.
    </p>

    <Button
        type="button"
        size="sm"
        variant="outline"
        onclick={panel.runTriage}
        disabled={panel.triageState === 'loading'}
        data-test="triage-run"
    >
        {panel.triageState === 'loading' ? 'Triaging…' : 'Run triage'}
    </Button>

    {#if panel.triageState === 'loading'}
        <div class="h-24 animate-pulse rounded-md bg-muted" data-test="triage-loading"></div>
    {/if}

    {#if panel.triageState === 'ready' && panel.triage === null}
        <p class="text-sm text-muted-foreground" data-test="triage-empty">
            Triage came back empty. Try again in a moment.
        </p>
    {/if}

    {#if panel.triageState === 'ready' && panel.triage !== null}
        <div class="flex flex-col gap-3 rounded-xl border p-4" data-test="triage-result">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-muted-foreground">Destination</span>
                <span class="text-sm font-medium">{humanize(panel.triage.destination)}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-muted-foreground">Suggested type</span>
                <span class="text-sm font-medium">{humanize(panel.triage.note_type)}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-muted-foreground">Reasoning</span>
                <p class="text-sm text-muted-foreground">{panel.triage.reasoning}</p>
            </div>

            <Form
                {...TriageController.applyType.form(note.slug)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                onSuccess={panel.clearTriage}
            >
                {#snippet children({ processing })}
                    <input type="hidden" name="note_type" value={panel.triage?.note_type} />
                    <Button type="submit" size="sm" disabled={processing} data-test="triage-apply">
                        Set type to {humanize(panel.triage?.note_type ?? '')}
                    </Button>
                {/snippet}
            </Form>
        </div>
    {/if}
</div>
