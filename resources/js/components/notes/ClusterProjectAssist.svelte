<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ClusterProjectController from '@/actions/App/Http/Controllers/Note/ClusterProjectController';
    import { Button } from '@/components/ui/button';
    import { getAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    const panel = getAssistPanelState();
</script>

<div class="flex flex-col gap-3" data-test="cluster-project-assist">
    <p class="text-sm text-muted-foreground">
        Group related notes into a project. Creating a project note does not rewrite this one.
    </p>

    <Button
        type="button"
        size="sm"
        variant="outline"
        onclick={panel.runClusterProject}
        disabled={panel.clusterState === 'loading'}
        data-test="cluster-project-run"
    >
        {panel.clusterState === 'loading' ? 'Clustering…' : 'Find ripe clusters'}
    </Button>

    {#if panel.clusterState === 'loading'}
        <ul class="flex flex-col gap-2" data-test="cluster-project-loading">
            {#each [0, 1] as i (i)}
                <li class="h-16 animate-pulse rounded-md bg-muted"></li>
            {/each}
        </ul>
    {/if}

    {#if panel.clusterState === 'ready' && panel.clusters.length === 0}
        <p class="text-sm text-muted-foreground" data-test="cluster-project-empty">
            No ripe clusters yet. Keep connecting notes.
        </p>
    {/if}

    {#if panel.clusterState === 'ready' && panel.clusters.length > 0}
        <ul class="flex flex-col gap-3" data-test="cluster-project-clusters">
            {#each panel.clusters as cluster, index (index)}
                <li class="flex flex-col gap-2 rounded-xl border p-3" data-test="cluster-project-cluster">
                    {#if cluster.assessment}
                        <p class="text-sm">{cluster.assessment}</p>
                    {:else}
                        <span class="text-sm text-muted-foreground">
                            Cluster of {cluster.note_ids.length} notes
                        </span>
                    {/if}
                    {#if cluster.gaps.length > 0}
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-muted-foreground">Gaps</span>
                            <ul class="list-disc pl-4">
                                {#each cluster.gaps as gap (gap)}
                                    <li class="text-xs text-muted-foreground">{gap}</li>
                                {/each}
                            </ul>
                        </div>
                    {/if}
                    <Form {...ClusterProjectController.create.form(note.slug)} resetOnSuccess>
                        {#snippet children({ processing })}
                            <input type="hidden" name="title" value="" />
                            {#each cluster.suggested_order as memberId (memberId)}
                                <input type="hidden" name="ordered_note_ids[]" value={memberId} />
                            {/each}
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                                data-test="cluster-project-create"
                            >
                                Create project note
                            </Button>
                        {/snippet}
                    </Form>
                </li>
            {/each}
        </ul>
    {/if}
</div>
