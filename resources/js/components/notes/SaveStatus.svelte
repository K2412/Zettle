<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import CircleDashed from '@lucide/svelte/icons/circle-dashed';
    import Loader2 from '@lucide/svelte/icons/loader-2';
    import type { SaveStatus } from '@/pages/notes/NoteEditorState.svelte';

    let { status }: { status: SaveStatus } = $props();

    const labels: Record<SaveStatus, string> = {
        saved: 'Saved',
        saving: 'Saving…',
        unsaved: 'Unsaved changes',
    };
</script>

<span
    class="inline-flex items-center gap-1.5 text-sm text-muted-foreground"
    data-test="save-status"
    data-status={status}
>
    {#if status === 'saving'}
        <Loader2 class="size-4 animate-spin" />
    {:else if status === 'saved'}
        <Check class="size-4" />
    {:else}
        <CircleDashed class="size-4" />
    {/if}
    {labels[status]}
</span>
