import { Form } from '@inertiajs/react';
import { useState } from 'react';
import TagMergeController from '@/actions/App/Http/Controllers/TagMergeController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Tag } from '@/types';

export function MergeTagDialog({
    tag,
    allTags,
    trigger,
}: {
    tag: Tag;
    allTags: Tag[];
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [targetTagId, setTargetTagId] = useState('');

    const targets = allTags
        .filter((other) => other.id !== tag.id)
        .sort((a, b) => a.name.localeCompare(b.name));

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Merge '{tag.name}' into…</DialogTitle>
                    <DialogDescription>
                        '{tag.name}' will be removed and its notes moved to the tag you choose.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...TagMergeController.store.form(tag.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                >
                    {({ processing }) => (
                        <>
                            <input type="hidden" name="target_tag_id" value={targetTagId} />
                            <Select value={targetTagId} onValueChange={setTargetTagId}>
                                <SelectTrigger className="w-full" data-test="tag-merge-target">
                                    <SelectValue placeholder="Choose the surviving tag" />
                                </SelectTrigger>
                                <SelectContent>
                                    {targets.map((target) => (
                                        <SelectItem
                                            key={target.id}
                                            value={String(target.id)}
                                            data-test={`tag-merge-option-${target.id}`}
                                        >
                                            {target.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing || targetTagId === ''}
                                    data-test="tag-merge-confirm"
                                >
                                    Merge tag
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
