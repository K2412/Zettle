<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import X from '@lucide/svelte/icons/x';
    import NoteTagController from '@/actions/App/Http/Controllers/Note/NoteTagController';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import type { Note, Tag } from '@/types';

    let {
        note,
        availableTags,
    }: {
        note: Note & { tags: Tag[] };
        availableTags: Tag[];
    } = $props();

    const reload = { only: ['note', 'availableTags'], preserveScroll: true };

    function attach(tag: Tag) {
        router.post(NoteTagController.store.url(note.slug), { tag_id: tag.id }, reload);
    }

    function detach(tag: Tag) {
        router.delete(
            NoteTagController.destroy.url({ note: note.slug, tag: tag.id }),
            reload,
        );
    }
</script>

<section class="flex flex-col gap-3" data-test="tags-panel">
    <h2 class="text-sm font-semibold text-muted-foreground">Tags</h2>

    <div class="flex flex-wrap gap-1.5" data-test="attached-tags">
        {#if note.tags.length === 0}
            <span class="text-xs text-muted-foreground">No tags yet.</span>
        {:else}
            {#each note.tags as tag (tag.id)}
                <Badge
                    variant="outline"
                    class="gap-1"
                    style={`border-color: ${tag.color}; color: ${tag.color}`}
                >
                    {tag.name}
                    <button
                        type="button"
                        onclick={() => detach(tag)}
                        aria-label={`Detach ${tag.name}`}
                        data-test="detach-tag"
                        class="hover:text-foreground"
                    >
                        <X class="size-3" />
                    </button>
                </Badge>
            {/each}
        {/if}
    </div>

    {#if availableTags.length > 0}
        <div class="flex flex-wrap gap-1.5" data-test="available-tags">
            {#each availableTags as tag (tag.id)}
                <button
                    type="button"
                    onclick={() => attach(tag)}
                    aria-label={`Attach ${tag.name}`}
                    data-test="attach-tag"
                >
                    <Badge variant="secondary" class="hover:bg-secondary/70">+ {tag.name}</Badge>
                </button>
            {/each}
        </div>
    {/if}

    <Form
        {...NoteTagController.store.form(note.slug)}
        options={{ only: ['note', 'availableTags'], preserveScroll: true }}
        resetOnSuccess
        class="flex gap-2"
        data-test="create-tag-form"
    >
        {#snippet children({ processing, errors })}
            <div class="flex w-full flex-col gap-1">
                <div class="flex gap-2">
                    <Input name="name" placeholder="New tag…" aria-label="New tag name" class="h-8" />
                    <Button type="submit" size="sm" disabled={processing}>Add</Button>
                </div>
                {#if errors.name}
                    <p class="text-xs text-destructive">{errors.name}</p>
                {/if}
            </div>
        {/snippet}
    </Form>
</section>
