import { Form } from '@inertiajs/react';
import TagController from '@/actions/App/Http/Controllers/TagController';
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
import type { Tag } from '@/types';

export function DeleteTagDialog({ tag, trigger }: { tag: Tag; trigger: React.ReactNode }) {
    const count = tag.notes_count ?? 0;
    const noun = count === 1 ? 'note' : 'notes';

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Remove '{tag.name}'?</DialogTitle>
                    <DialogDescription>
                        This removes it from {count} {noun}. The notes stay; the tag is gone.
                    </DialogDescription>
                </DialogHeader>
                <Form {...TagController.destroy.form(tag.id)} options={{ preserveScroll: true }}>
                    {({ processing }) => (
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                data-test="tag-delete-confirm"
                            >
                                Remove tag
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
