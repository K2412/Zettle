<script module lang="ts">
    import { index } from '@/routes/notes';

    export const layout = {
        breadcrumbs: [{ title: 'Notes', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import NoteController from '@/actions/App/Http/Controllers/NoteController';
    import NoteCard from '@/components/notes/NoteCard.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { NOTE_TYPES } from '@/lib/note-types';
    import { untrack } from 'svelte';
    import type { Note, NoteFilters, Paginated, Tag } from '@/types';

    let {
        notes,
        tags,
        filters,
    }: {
        notes: Paginated<Note>;
        tags: Tag[];
        filters: NoteFilters;
    } = $props();

    let search = $state(untrack(() => filters.q ?? ''));

    function runSearch(event: Event) {
        event.preventDefault();
        router.get(
            index().url,
            { q: search || undefined, tagId: filters.tagId ?? undefined },
            {
                only: ['notes', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function filterByTag(tagId: number | null) {
        router.get(
            index().url,
            { q: search || undefined, tagId: tagId ?? undefined },
            { only: ['notes', 'filters'], preserveScroll: true, replace: true },
        );
    }
</script>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
    <AppHead title="Notes" />

    <Form
        {...NoteController.store.form()}
        resetOnSuccess
        class="flex flex-col gap-2 sm:flex-row"
        data-test="create-note-form"
    >
        {#snippet children({ processing, errors })}
            <Input
                name="title"
                placeholder="A new spark…"
                aria-label="New note title"
                class="flex-1"
                required
            />
            <select
                name="note_type"
                aria-label="Note type"
                class="h-9 w-full rounded-md border bg-transparent px-2 text-sm sm:w-40"
            >
                {#each NOTE_TYPES as type (type.value)}
                    <option value={type.value}>{type.label}</option>
                {/each}
            </select>
            <Button type="submit" disabled={processing}>Create</Button>
            {#if errors.title}
                <p class="text-sm text-destructive">{errors.title}</p>
            {/if}
        {/snippet}
    </Form>

    <form onsubmit={runSearch} class="flex gap-2" data-test="search-form">
        <Input
            type="search"
            bind:value={search}
            placeholder="Search notes…"
            aria-label="Search notes"
            data-test="search-notes"
            class="flex-1"
        />
        <Button type="submit" data-test="search-submit">Search</Button>
    </form>

    <div class="flex flex-wrap gap-2" data-test="tag-filter">
        <button type="button" onclick={() => filterByTag(null)}>
            <Badge variant={filters.tagId === null ? 'default' : 'outline'}>All</Badge>
        </button>
        {#each tags as tag (tag.id)}
            <button type="button" onclick={() => filterByTag(tag.id)}>
                <Badge
                    variant={filters.tagId === tag.id ? 'default' : 'outline'}
                    style={filters.tagId === tag.id
                        ? undefined
                        : `border-color: ${tag.color}; color: ${tag.color}`}
                >
                    {tag.name}
                </Badge>
            </button>
        {/each}
    </div>

    {#if notes.data.length === 0}
        <div
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
            data-test="empty-state"
        >
            <p class="font-medium">No notes yet.</p>
            <p class="text-sm">Create your first spark above.</p>
        </div>
    {:else}
        <div class="flex flex-col gap-3">
            {#each notes.data as note (note.id)}
                <NoteCard {note} />
            {/each}
        </div>
    {/if}

    {#if notes.last_page > 1}
        <div class="flex items-center justify-center gap-2" data-test="pagination">
            {#each notes.links as link, i (i)}
                {#if link.url}
                    <Link
                        href={link.url}
                        only={['notes', 'filters']}
                        preserveScroll
                        class={`rounded-md px-3 py-1 text-sm ${
                            link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'
                        }`}
                    >
                        {@html link.label}
                    </Link>
                {:else}
                    <span class="px-3 py-1 text-sm text-muted-foreground">{@html link.label}</span>
                {/if}
            {/each}
        </div>
    {/if}
</div>
