import { Form } from '@inertiajs/react';
import NoteController from '@/actions/App/Http/Controllers/NoteController';
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
import type { Note } from '@/types';

export function DeleteNoteDialog({ note, trigger }: { note: Note; trigger: React.ReactNode }) {
    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete note</DialogTitle>
                    <DialogDescription>
                        Delete <strong>{note.title}</strong>? This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...NoteController.destroy.form(note.slug)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" variant="destructive" disabled={processing} data-test="confirm-delete">
                                Delete
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
