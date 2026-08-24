import { Form, router } from '@inertiajs/react';
import { X } from 'lucide-react';
import NoteTagController from '@/actions/App/Http/Controllers/Note/NoteTagController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Note, Tag } from '@/types';

type Props = {
    note: Note & { tags: Tag[] };
    availableTags: Tag[];
};

const RELOAD = { only: ['note', 'availableTags'], preserveScroll: true };

export function NoteTagsPanel({ note, availableTags }: Props) {
    const attach = (tag: Tag) => {
        router.post(
            NoteTagController.store.url(note.slug),
            { tag_id: tag.id },
            RELOAD,
        );
    };

    const detach = (tag: Tag) => {
        router.delete(NoteTagController.destroy.url({ note: note.slug, tag: tag.id }), RELOAD);
    };

    return (
        <section className="flex flex-col gap-3" data-test="tags-panel">
            <h2 className="text-sm font-semibold text-muted-foreground">Tags</h2>

            <div className="flex flex-wrap gap-1.5" data-test="attached-tags">
                {note.tags.length === 0 ? (
                    <span className="text-xs text-muted-foreground">No tags yet.</span>
                ) : (
                    note.tags.map((tag) => (
                        <Badge
                            key={tag.id}
                            variant="outline"
                            style={{ borderColor: tag.color, color: tag.color }}
                            className="gap-1"
                        >
                            {tag.name}
                            <button
                                type="button"
                                onClick={() => detach(tag)}
                                aria-label={`Detach ${tag.name}`}
                                data-test="detach-tag"
                                className="hover:text-foreground"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))
                )}
            </div>

            {availableTags.length > 0 && (
                <div className="flex flex-wrap gap-1.5" data-test="available-tags">
                    {availableTags.map((tag) => (
                        <button
                            key={tag.id}
                            type="button"
                            onClick={() => attach(tag)}
                            aria-label={`Attach ${tag.name}`}
                            data-test="attach-tag"
                        >
                            <Badge variant="secondary" className="hover:bg-secondary/70">
                                + {tag.name}
                            </Badge>
                        </button>
                    ))}
                </div>
            )}

            <Form
                {...NoteTagController.store.form(note.slug)}
                options={{ only: ['note', 'availableTags'], preserveScroll: true }}
                resetOnSuccess
                className="flex gap-2"
                data-test="create-tag-form"
            >
                {({ processing, errors }) => (
                    <div className="flex w-full flex-col gap-1">
                        <div className="flex gap-2">
                            <Input
                                name="name"
                                placeholder="New tag…"
                                aria-label="New tag name"
                                className="h-8"
                            />
                            <Button type="submit" size="sm" disabled={processing}>
                                Add
                            </Button>
                        </div>
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>
                )}
            </Form>
        </section>
    );
}
