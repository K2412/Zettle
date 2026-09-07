<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import DeleteNoteDialog from '@/components/notes/DeleteNoteDialog.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { noteTypeLabel } from '@/lib/note-types';
    import { show } from '@/routes/notes';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();
</script>

<Card class="group relative gap-3 py-4" data-test="note-card">
    <CardHeader class="px-4">
        <div class="flex items-start justify-between gap-2">
            <CardTitle class="text-base">
                <Link href={show(note.slug)} class="hover:underline">{note.title}</Link>
            </CardTitle>
            <DeleteNoteDialog {note}>
                {#snippet children(props)}
                    <Button
                        {...props}
                        variant="ghost"
                        size="icon"
                        class="size-7 text-muted-foreground opacity-60 transition-opacity hover:opacity-100 focus-visible:opacity-100 group-hover:opacity-100"
                        aria-label={`Delete ${note.title}`}
                        data-test="delete-note"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                {/snippet}
            </DeleteNoteDialog>
        </div>
    </CardHeader>
    <CardContent class="flex flex-wrap items-center gap-2 px-4">
        <Badge variant="secondary">{noteTypeLabel(note.note_type)}</Badge>
        {#each note.tags ?? [] as tag (tag.id)}
            <Badge variant="outline" style={`border-color: ${tag.color}; color: ${tag.color}`}>
                {tag.name}
            </Badge>
        {/each}
    </CardContent>
</Card>
