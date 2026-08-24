import { Form } from '@inertiajs/react';
import { GitMerge, Trash2 } from 'lucide-react';
import TagController from '@/actions/App/Http/Controllers/TagController';
import InputError from '@/components/input-error';
import { DeleteTagDialog } from '@/components/tags/delete-tag-dialog';
import { MergeTagDialog } from '@/components/tags/merge-tag-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Tag } from '@/types';

export function TagRow({ tag, allTags }: { tag: Tag; allTags: Tag[] }) {
    // Server props own name/color; the inputs are uncontrolled so a save always
    // reflects what was stored (the page keys each row on tag id+name+color, so
    // fresh props remount it rather than stranding stale local state).
    return (
        <Form
            {...TagController.update.form(tag.id)}
            options={{ preserveScroll: true }}
            className="flex flex-col gap-1 rounded-xl border p-3"
            data-test="tag-row"
        >
            {({ processing, errors }) => (
                <>
                    <div className="flex items-center gap-3">
                        <input
                            type="color"
                            name="color"
                            defaultValue={tag.color}
                            aria-label={`Color for ${tag.name}`}
                            data-test="tag-color-input"
                            className="size-8 shrink-0 cursor-pointer rounded-full border bg-transparent"
                        />
                        <Input
                            name="name"
                            defaultValue={tag.name}
                            aria-label={`Name for ${tag.name}`}
                            data-test="tag-name-input"
                            className="font-medium"
                        />
                        <span className="whitespace-nowrap text-sm text-muted-foreground">
                            · {tag.notes_count ?? 0} notes
                        </span>
                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing}
                            data-test="tag-save"
                        >
                            Save
                        </Button>
                        <MergeTagDialog
                            tag={tag}
                            allTags={allTags}
                            trigger={
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    aria-label={`Merge ${tag.name}`}
                                    data-test="tag-merge"
                                    className="text-muted-foreground hover:text-foreground"
                                >
                                    <GitMerge />
                                </Button>
                            }
                        />
                        <DeleteTagDialog
                            tag={tag}
                            trigger={
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    aria-label={`Delete ${tag.name}`}
                                    data-test="tag-delete"
                                    className="text-muted-foreground hover:text-destructive"
                                >
                                    <Trash2 />
                                </Button>
                            }
                        />
                    </div>
                    <InputError message={errors.name} />
                    <InputError message={errors.color} />
                </>
            )}
        </Form>
    );
}
