import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { TagRow } from '@/components/tags/tag-row';
import { index } from '@/routes/tags';
import type { Tag } from '@/types';

type Props = {
    tags: Tag[];
};

export default function TagsIndex({ tags }: Props) {
    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
            <Head title="Tags" />

            <Heading title="Tags" />

            {tags.length === 0 ? (
                <div
                    className="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
                    data-test="tags-empty-state"
                >
                    <p className="font-medium">No tags yet.</p>
                    <p className="text-sm">Tag a note to grow your first one.</p>
                </div>
            ) : (
                <div className="flex flex-col gap-3">
                    {tags.map((tag) => (
                        <TagRow
                            key={`${tag.id}-${tag.name}-${tag.color}`}
                            tag={tag}
                            allTags={tags}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

TagsIndex.layout = {
    breadcrumbs: [{ title: 'Tags', href: index() }],
};
