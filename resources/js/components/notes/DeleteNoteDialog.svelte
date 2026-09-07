<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Form } from '@inertiajs/svelte';
    import NoteController from '@/actions/App/Http/Controllers/NoteController';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import type { Note } from '@/types';

    let {
        note,
        children: trigger,
    }: {
        note: Note;
        children?: Snippet<[Record<string, unknown>]>;
    } = $props();
</script>

<Dialog>
    <DialogTrigger asChild>
        {#snippet children(props)}
            {@render trigger?.(props)}
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Delete note</DialogTitle>
        <DialogDescription>
            Delete <strong>{note.title}</strong>? This cannot be undone.
        </DialogDescription>
        <Form {...NoteController.destroy.form(note.slug)} options={{ preserveScroll: true }}>
            {#snippet children({ processing })}
                <DialogFooter>
                    <DialogClose asChild>
                        {#snippet children(closeProps)}
                            <Button type="button" variant="outline" {...closeProps}>
                                Cancel
                            </Button>
                        {/snippet}
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        disabled={processing}
                        data-test="confirm-delete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
