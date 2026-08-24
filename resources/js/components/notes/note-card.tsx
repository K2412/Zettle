import { Link } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { DeleteNoteDialog } from '@/components/notes/delete-note-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { noteTypeLabel } from '@/lib/note-types';
import { show } from '@/routes/notes';
import type { Note } from '@/types';

export function NoteCard({ note }: { note: Note }) {
    return (
        <Card className="group relative gap-3 py-4" data-test="note-card">
            <CardHeader className="px-4">
                <div className="flex items-start justify-between gap-2">
                    <CardTitle className="text-base">
                        <Link href={show(note.slug)} className="hover:underline">
                            {note.title}
                        </Link>
                    </CardTitle>
                    <DeleteNoteDialog
                        note={note}
                        trigger={
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-7 text-muted-foreground opacity-60 transition-opacity hover:opacity-100 focus-visible:opacity-100 group-hover:opacity-100"
                                aria-label={`Delete ${note.title}`}
                                data-test="delete-note"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        }
                    />
                </div>
            </CardHeader>
            <CardContent className="flex flex-wrap items-center gap-2 px-4">
                <Badge variant="secondary">{noteTypeLabel(note.note_type)}</Badge>
                {note.tags?.map((tag) => (
                    <Badge
                        key={tag.id}
                        variant="outline"
                        style={{ borderColor: tag.color, color: tag.color }}
                    >
                        {tag.name}
                    </Badge>
                ))}
            </CardContent>
        </Card>
    );
}
