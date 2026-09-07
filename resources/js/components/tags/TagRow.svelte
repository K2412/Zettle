<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import GitMerge from '@lucide/svelte/icons/git-merge';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import TagController from '@/actions/App/Http/Controllers/TagController';
    import InputError from '@/components/InputError.svelte';
    import DeleteTagDialog from '@/components/tags/DeleteTagDialog.svelte';
    import MergeTagDialog from '@/components/tags/MergeTagDialog.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import type { Tag } from '@/types';

    let { tag, allTags }: { tag: Tag; allTags: Tag[] } = $props();
</script>

<Form
    {...TagController.update.form(tag.id)}
    options={{ preserveScroll: true }}
    class="flex flex-col gap-1 rounded-xl border p-3"
    data-test="tag-row"
    data-tag-name={tag.name}
>
    {#snippet children({ processing, errors })}
        <div class="flex items-center gap-3">
            <input
                type="color"
                name="color"
                value={tag.color}
                aria-label={`Color for ${tag.name}`}
                data-test="tag-color-input"
                class="size-8 shrink-0 cursor-pointer rounded-full border bg-transparent"
            />
            <Input
                name="name"
                value={tag.name}
                aria-label={`Name for ${tag.name}`}
                data-test="tag-name-input"
                class="font-medium"
            />
            <span class="whitespace-nowrap text-sm text-muted-foreground">
                · {tag.notes_count ?? 0} notes
            </span>
            <Button type="submit" size="sm" disabled={processing} data-test="tag-save">Save</Button>
            <MergeTagDialog {tag} {allTags}>
                {#snippet children(props)}
                    <Button
                        {...props}
                        type="button"
                        size="sm"
                        variant="ghost"
                        aria-label={`Merge ${tag.name}`}
                        data-test="tag-merge"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <GitMerge />
                    </Button>
                {/snippet}
            </MergeTagDialog>
            <DeleteTagDialog {tag}>
                {#snippet children(props)}
                    <Button
                        {...props}
                        type="button"
                        size="sm"
                        variant="ghost"
                        aria-label={`Delete ${tag.name}`}
                        data-test="tag-delete"
                        class="text-muted-foreground hover:text-destructive"
                    >
                        <Trash2 />
                    </Button>
                {/snippet}
            </DeleteTagDialog>
        </div>
        <InputError message={errors.name} />
        <InputError message={errors.color} />
    {/snippet}
</Form>
