<script module lang="ts">
    import { index } from '@/routes/tags';

    export const layout = {
        breadcrumbs: [{ title: 'Tags', href: index() }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import TagRow from '@/components/tags/TagRow.svelte';
    import type { Tag } from '@/types';

    let { tags }: { tags: Tag[] } = $props();
</script>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <AppHead title="Tags" />

    <Heading title="Tags" />

    {#if tags.length === 0}
        <div
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
            data-test="tags-empty-state"
        >
            <p class="font-medium">No tags yet.</p>
            <p class="text-sm">Tag a note to grow your first one.</p>
        </div>
    {:else}
        <div class="flex flex-col gap-3">
            {#each tags as tag (`${tag.id}-${tag.name}-${tag.color}`)}
                <TagRow {tag} allTags={tags} />
            {/each}
        </div>
    {/if}
</div>
