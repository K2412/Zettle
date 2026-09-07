<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { show } from '@/routes/notes';
    import type { NoteLink } from '@/types';

    let {
        outgoingLinks,
        backlinks,
    }: {
        outgoingLinks: NoteLink[];
        backlinks: NoteLink[];
    } = $props();
</script>

<section class="flex flex-col gap-2" data-test="outgoing-links">
    <h2 class="text-sm font-semibold text-muted-foreground">
        Links to ({outgoingLinks.length})
    </h2>
    {#if outgoingLinks.length === 0}
        <span class="text-xs text-muted-foreground">None yet.</span>
    {:else}
        <ul class="flex flex-col gap-1">
            {#each outgoingLinks as link (link.id)}
                <li>
                    <Link href={show(link.slug)} class="text-sm hover:underline" data-test="note-link">
                        {link.title}
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}
</section>

<section class="flex flex-col gap-2" data-test="backlinks">
    <h2 class="text-sm font-semibold text-muted-foreground">
        Backlinks ({backlinks.length})
    </h2>
    {#if backlinks.length === 0}
        <span class="text-xs text-muted-foreground">None yet.</span>
    {:else}
        <ul class="flex flex-col gap-1">
            {#each backlinks as link (link.id)}
                <li>
                    <Link href={show(link.slug)} class="text-sm hover:underline" data-test="note-link">
                        {link.title}
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}
</section>
