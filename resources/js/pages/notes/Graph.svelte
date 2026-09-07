<script module lang="ts">
    import { graph as graphRoute } from '@/routes/notes';

    export const layout = {
        breadcrumbs: [{ title: 'Graph', href: graphRoute() }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { GraphState } from '@/pages/notes/GraphState.svelte';
    import type { GraphData } from '@/types';

    let { graph }: { graph: GraphData } = $props();

    const graphState = new GraphState();
    let canvas: HTMLCanvasElement | undefined = $state();
    const nodeCount = $derived(graph.nodes.length);
    const connectionCount = $derived(graph.edges.length);

    $effect(() => {
        const el = canvas;
        const data = graph;

        if (!el) {
            return;
        }

        graphState.attach(el, data);

        return () => graphState.detach();
    });
</script>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
    <AppHead title="Graph" />

    <header class="flex flex-col gap-1">
        <h2 class="text-xl font-semibold tracking-tight">Graph</h2>
        <p class="text-sm text-muted-foreground" data-test="graph-counts">
            {nodeCount}
            {nodeCount === 1 ? 'note' : 'notes'} ·
            {connectionCount}
            {connectionCount === 1 ? 'connection' : 'connections'}
        </p>
    </header>

    {#if nodeCount === 0}
        <div
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
            data-test="empty-state"
        >
            <p class="font-medium">No notes to visualize yet.</p>
            <p class="text-sm">Create a few and link them with [[Note Title]].</p>
        </div>
    {:else}
        <div class="h-[70vh] w-full overflow-hidden rounded-xl border" data-test="graph-canvas">
            <canvas
                bind:this={canvas}
                class="h-full w-full text-foreground"
                data-test="graph-canvas-el"
            ></canvas>
        </div>
    {/if}
</div>
