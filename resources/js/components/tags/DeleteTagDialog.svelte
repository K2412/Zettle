<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import TagController from '@/actions/App/Http/Controllers/TagController';
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
    import type { Tag } from '@/types';

    let {
        tag,
        children: trigger,
    }: {
        tag: Tag;
        children?: import('svelte').Snippet<[Record<string, unknown>]>;
    } = $props();

    const count = $derived(tag.notes_count ?? 0);
    const noun = $derived(count === 1 ? 'note' : 'notes');
</script>

<Dialog>
    <DialogTrigger asChild>
        {#snippet children(props)}
            {@render trigger?.(props)}
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Remove '{tag.name}'?</DialogTitle>
        <DialogDescription>
            This removes it from {count} {noun}. The notes stay; the tag is gone.
        </DialogDescription>
        <Form {...TagController.destroy.form(tag.id)} options={{ preserveScroll: true }}>
            {#snippet children({ processing })}
                <DialogFooter>
                    <DialogClose asChild>
                        {#snippet children(closeProps)}
                            <Button type="button" variant="outline" {...closeProps}>Cancel</Button>
                        {/snippet}
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        disabled={processing}
                        data-test="tag-delete-confirm"
                    >
                        Remove tag
                    </Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
