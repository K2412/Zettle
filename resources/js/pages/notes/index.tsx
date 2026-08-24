import { Form, Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import NoteController from '@/actions/App/Http/Controllers/NoteController';
import { NoteCard } from '@/components/notes/note-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { NOTE_TYPES } from '@/lib/note-types';
import { index } from '@/routes/notes';
import type { NoteFilters, Note, Paginated, Tag } from '@/types';

type Props = {
    notes: Paginated<Note>;
    tags: Tag[];
    filters: NoteFilters;
};

export default function NotesIndex({ notes, tags, filters }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');

    // Search runs only on an explicit submit — one query embedding per search,
    // not one per keystroke — reloading just the notes list and keeping scroll.
    const runSearch = (event: FormEvent) => {
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
    };

    const filterByTag = (tagId: number | null) => {
        router.get(
            index().url,
            { q: search || undefined, tagId: tagId ?? undefined },
            { only: ['notes', 'filters'], preserveScroll: true, replace: true },
        );
    };

    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
            <Head title="Notes" />

            <Form
                {...NoteController.store.form()}
                resetOnSuccess
                className="flex flex-col gap-2 sm:flex-row"
                data-test="create-note-form"
            >
                {({ processing, errors }) => (
                    <>
                        <Input
                            name="title"
                            placeholder="A new spark…"
                            aria-label="New note title"
                            className="flex-1"
                            required
                        />
                        <Select name="note_type" defaultValue="fleeting">
                            <SelectTrigger
                                className="w-full sm:w-40"
                                aria-label="Note type"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {NOTE_TYPES.map((type) => (
                                    <SelectItem
                                        key={type.value}
                                        value={type.value}
                                    >
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button type="submit" disabled={processing}>
                            Create
                        </Button>
                        {errors.title && (
                            <p className="text-sm text-destructive">
                                {errors.title}
                            </p>
                        )}
                    </>
                )}
            </Form>

            <form
                onSubmit={runSearch}
                className="flex gap-2"
                data-test="search-form"
            >
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search notes…"
                    aria-label="Search notes"
                    data-test="search-notes"
                    className="flex-1"
                />
                <Button type="submit" data-test="search-submit">
                    Search
                </Button>
            </form>

            <div className="flex flex-wrap gap-2" data-test="tag-filter">
                <button type="button" onClick={() => filterByTag(null)}>
                    <Badge
                        variant={filters.tagId === null ? 'default' : 'outline'}
                    >
                        All
                    </Badge>
                </button>
                {tags.map((tag) => (
                    <button
                        key={tag.id}
                        type="button"
                        onClick={() => filterByTag(tag.id)}
                    >
                        <Badge
                            variant={
                                filters.tagId === tag.id ? 'default' : 'outline'
                            }
                            style={
                                filters.tagId === tag.id
                                    ? undefined
                                    : {
                                          borderColor: tag.color,
                                          color: tag.color,
                                      }
                            }
                        >
                            {tag.name}
                        </Badge>
                    </button>
                ))}
            </div>

            {notes.data.length === 0 ? (
                <div
                    className="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
                    data-test="empty-state"
                >
                    <p className="font-medium">No notes yet.</p>
                    <p className="text-sm">Create your first spark above.</p>
                </div>
            ) : (
                <div className="flex flex-col gap-3">
                    {notes.data.map((note) => (
                        <NoteCard key={note.id} note={note} />
                    ))}
                </div>
            )}

            {notes.last_page > 1 && (
                <div
                    className="flex items-center justify-center gap-2"
                    data-test="pagination"
                >
                    {notes.links.map((link, i) =>
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                only={['notes', 'filters']}
                                preserveScroll
                                className={`rounded-md px-3 py-1 text-sm ${
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-accent'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={i}
                                className="px-3 py-1 text-sm text-muted-foreground"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </div>
            )}
        </div>
    );
}

NotesIndex.layout = {
    breadcrumbs: [{ title: 'Notes', href: index() }],
};
