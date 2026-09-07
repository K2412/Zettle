<script lang="ts">
    import { untrack } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import MarkdownEditor from '@/components/notes/MarkdownEditor.svelte';
    import NotePreview from '@/components/notes/NotePreview.svelte';
    import NoteSidebar from '@/components/notes/NoteSidebar.svelte';
    import SaveStatus from '@/components/notes/SaveStatus.svelte';
    import { Input } from '@/components/ui/input';
    import { wikilinkCompletionSource } from '@/lib/wikilink-autocomplete';
    import { setAssistPanelState } from '@/pages/notes/AssistPanelState.svelte';
    import { NoteEditorState } from '@/pages/notes/NoteEditorState.svelte';
    import type {
        Connection,
        Note,
        NoteLink,
        PhaseOption,
        RelationshipGroup,
        Tag,
    } from '@/types';

    let {
        note,
        outgoingLinks,
        backlinks,
        connections,
        incomingConnections,
        relationshipOptions,
        availableTags,
        titleToSlug,
        suggestedPhase,
        phases,
    }: {
        note: Note & { tags: Tag[] };
        outgoingLinks: NoteLink[];
        backlinks: NoteLink[];
        connections: Connection[];
        incomingConnections: Connection[];
        relationshipOptions: RelationshipGroup[];
        availableTags: Tag[];
        titleToSlug: Record<string, string>;
        suggestedPhase: string;
        phases: PhaseOption[];
    } = $props();

    let tab = $state<'write' | 'preview'>('write');
    let editor = $state(new NoteEditorState(untrack(() => note)));
    const panel = setAssistPanelState(
        untrack(() => note),
        untrack(() => suggestedPhase),
        untrack(() => phases),
    );
    const completionSource = $derived(wikilinkCompletionSource(note.id));

    let lastNoteId = 0;

    $effect(() => {
        const id = note.id;
        panel.note = untrack(() => note);
        panel.suggestedPhase = suggestedPhase;
        panel.phases = phases;

        if (id !== lastNoteId) {
            lastNoteId = id;
            panel.activePhase = suggestedPhase;
        }
    });

    $effect(() => {
        const id = note.id;
        void id;
        const next = new NoteEditorState(untrack(() => note));
        editor = next;

        return next.start();
    });
</script>

<div class="mx-auto flex w-full max-w-5xl gap-6 p-4">
    <AppHead title={editor.title || 'Note'} />

    <div class="flex min-w-0 flex-1 flex-col gap-3">
        <div class="flex items-center justify-between gap-3">
            <Input
                value={editor.title}
                oninput={(event) =>
                    editor.setField('title', (event.currentTarget as HTMLInputElement).value)}
                aria-label="Note title"
                data-test="note-title"
                class="border-none px-0 text-2xl font-semibold shadow-none focus-visible:ring-0"
                placeholder="Untitled"
            />
            <SaveStatus status={editor.status} />
        </div>

        <div class="flex flex-col gap-3">
            <div class="flex gap-1.5" role="tablist">
                <button
                    type="button"
                    role="tab"
                    aria-selected={tab === 'write'}
                    onclick={() => (tab = 'write')}
                    data-test="tab-write"
                    class={tab === 'write'
                        ? 'rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground'
                        : 'rounded-md border px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground'}
                >
                    Write
                </button>
                <button
                    type="button"
                    role="tab"
                    aria-selected={tab === 'preview'}
                    onclick={() => (tab = 'preview')}
                    data-test="tab-preview"
                    class={tab === 'preview'
                        ? 'rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground'
                        : 'rounded-md border px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground'}
                >
                    Preview
                </button>
            </div>

            {#if tab === 'write'}
                <MarkdownEditor
                    value={editor.body}
                    onChange={(value) => editor.setField('body', value)}
                    {completionSource}
                    ariaLabel="Note body"
                    placeholder="Start writing… use [[Note Title]] to link."
                />
            {:else}
                <NotePreview body={editor.body} {titleToSlug} />
            {/if}
        </div>
    </div>

    <NoteSidebar
        {note}
        {availableTags}
        {outgoingLinks}
        {backlinks}
        {connections}
        {incomingConnections}
        {relationshipOptions}
    />
</div>
