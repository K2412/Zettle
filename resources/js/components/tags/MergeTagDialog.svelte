<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import TagMergeController from '@/actions/App/Http/Controllers/TagMergeController';
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
        allTags,
        children: trigger,
    }: {
        tag: Tag;
        allTags: Tag[];
        children?: import('svelte').Snippet<[Record<string, unknown>]>;
    } = $props();

    let open = $state(false);
    let targetTagId = $state('');

    const targets = $derived(
        allTags.filter((other) => other.id !== tag.id).sort((a, b) => a.name.localeCompare(b.name)),
    );
</script>

<Dialog bind:open onOpenChange={(next) => (open = next)}>
    <DialogTrigger asChild>
        {#snippet children(props)}
            {@render trigger?.(props)}
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        <DialogTitle>Merge '{tag.name}' into…</DialogTitle>
        <DialogDescription>
            '{tag.name}' will be removed and its notes moved to the tag you choose.
        </DialogDescription>
        <Form
            {...TagMergeController.store.form(tag.id)}
            options={{ preserveScroll: true }}
            onSuccess={() => (open = false)}
        >
            {#snippet children({ processing })}
                <select
                    bind:value={targetTagId}
                    name="target_tag_id"
                    aria-label="Surviving tag"
                    data-test="tag-merge-target"
                    class="h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                >
                    <option value="">Choose the surviving tag</option>
                    {#each targets as target (target.id)}
                        <option value={String(target.id)} data-test={`tag-merge-option-${target.id}`}>
                            {target.name}
                        </option>
                    {/each}
                </select>
                <DialogFooter>
                    <DialogClose asChild>
                        {#snippet children(closeProps)}
                            <Button type="button" variant="outline" {...closeProps}>Cancel</Button>
                        {/snippet}
                    </DialogClose>
                    <Button
                        type="submit"
                        disabled={processing || targetTagId === ''}
                        data-test="tag-merge-confirm"
                    >
                        Merge tag
                    </Button>
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
