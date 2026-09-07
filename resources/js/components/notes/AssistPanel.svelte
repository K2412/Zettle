<script lang="ts">
    import AtomizeAssist from '@/components/notes/AtomizeAssist.svelte';
    import ClusterProjectAssist from '@/components/notes/ClusterProjectAssist.svelte';
    import ConnectAssist from '@/components/notes/ConnectAssist.svelte';
    import FormulateAssist from '@/components/notes/FormulateAssist.svelte';
    import MakeFindableAssist from '@/components/notes/MakeFindableAssist.svelte';
    import StructureAssist from '@/components/notes/StructureAssist.svelte';
    import TriageAssist from '@/components/notes/TriageAssist.svelte';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<section class="flex flex-col gap-3 border-t pt-4" data-test="assist-panel" aria-label="Assists">
    <h2 class="text-sm font-semibold text-muted-foreground">Assists</h2>

    <div class="flex flex-wrap gap-1.5" role="tablist">
        {#each panel.phases as phase (phase.value)}
            {@const isSuggested = phase.value === panel.suggestedPhase}
            {@const isActive = phase.value === panel.activePhase}
            <button
                type="button"
                role="tab"
                aria-selected={isActive}
                onclick={() => panel.setPhase(phase.value)}
                data-test={`phase-tab-${phase.value}`}
                class={isActive
                    ? 'rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground'
                    : 'rounded-md border px-2 py-1 text-xs text-muted-foreground hover:text-foreground'}
            >
                {phase.label}
                {#if isSuggested}
                    <span class="ml-1 opacity-70">· suggested</span>
                {/if}
            </button>
        {/each}
    </div>

    <div data-test="assist-active">
        {#if panel.activePhase === 'triage'}
            <TriageAssist {note} />
        {:else if panel.activePhase === 'atomize'}
            <AtomizeAssist {note} />
        {:else if panel.activePhase === 'formulate'}
            <FormulateAssist {note} />
        {:else if panel.activePhase === 'connect'}
            <ConnectAssist {note} />
        {:else if panel.activePhase === 'make_findable'}
            <MakeFindableAssist {note} />
        {:else if panel.activePhase === 'structure'}
            <StructureAssist {note} />
        {:else if panel.activePhase === 'cluster_project'}
            <ClusterProjectAssist {note} />
        {/if}
    </div>
</section>
