import { Head } from '@inertiajs/react';
import { lazy, Suspense, useMemo, useState } from 'react';
import { NotePreview } from '@/components/notes/note-preview';
import { NoteSidebar } from '@/components/notes/note-sidebar';
import { SaveStatusIndicator } from '@/components/notes/save-status';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useNoteEditor } from '@/hooks/use-note-editor';
import { wikilinkCompletionSource } from '@/lib/wikilink-autocomplete';
import type { Connection, Note, NoteLink, PhaseOption, RelationshipGroup, Tag } from '@/types';

const MarkdownEditor = lazy(() =>
    import('@/components/notes/markdown-editor').then((m) => ({ default: m.MarkdownEditor })),
);

type Props = {
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
};

export default function NotesShow({
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
}: Props) {
    const { draft, status, setField } = useNoteEditor(note);
    const [tab, setTab] = useState<'write' | 'preview'>('write');

    // Keyed on note.id so an autosave partial reload (which hands back a fresh
    // `note` object) doesn't produce a new source identity and rebuild CodeMirror
    // — that would close the open [[ completion popup and drop the caret.
    const completionSource = useMemo(() => wikilinkCompletionSource(note.id), [note.id]);

    return (
        <div className="mx-auto flex w-full max-w-5xl gap-6 p-4">
            <Head title={draft.title || 'Note'} />

            <div className="flex min-w-0 flex-1 flex-col gap-3">
                <div className="flex items-center justify-between gap-3">
                    <Input
                        value={draft.title}
                        onChange={(e) => setField('title', e.target.value)}
                        aria-label="Note title"
                        data-test="note-title"
                        className="border-none px-0 text-2xl font-semibold shadow-none focus-visible:ring-0"
                        placeholder="Untitled"
                    />
                    <SaveStatusIndicator status={status} />
                </div>

                <Tabs value={tab} onValueChange={(v) => setTab(v as 'write' | 'preview')}>
                    <TabsList>
                        <TabsTrigger value="write" data-test="tab-write">
                            Write
                        </TabsTrigger>
                        <TabsTrigger value="preview" data-test="tab-preview">
                            Preview
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="write">
                        <Suspense fallback={<Skeleton className="h-80 w-full rounded-md" />}>
                            <MarkdownEditor
                                value={draft.body}
                                onChange={(value) => setField('body', value)}
                                completionSource={completionSource}
                                ariaLabel="Note body"
                                placeholder="Start writing… use [[Note Title]] to link."
                            />
                        </Suspense>
                    </TabsContent>

                    <TabsContent value="preview">
                        <NotePreview body={draft.body} titleToSlug={titleToSlug} />
                    </TabsContent>
                </Tabs>
            </div>

            <NoteSidebar
                note={note}
                availableTags={availableTags}
                outgoingLinks={outgoingLinks}
                backlinks={backlinks}
                connections={connections}
                incomingConnections={incomingConnections}
                relationshipOptions={relationshipOptions}
                suggestedPhase={suggestedPhase}
                phases={phases}
            />
        </div>
    );
}
