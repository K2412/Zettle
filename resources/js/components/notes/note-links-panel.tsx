import { Link } from '@inertiajs/react';
import { show } from '@/routes/notes';
import type { NoteLink } from '@/types';

function LinkList({ title, links, testId }: { title: string; links: NoteLink[]; testId: string }) {
    return (
        <section className="flex flex-col gap-2" data-test={testId}>
            <h2 className="text-sm font-semibold text-muted-foreground">
                {title} ({links.length})
            </h2>
            {links.length === 0 ? (
                <span className="text-xs text-muted-foreground">None yet.</span>
            ) : (
                <ul className="flex flex-col gap-1">
                    {links.map((link) => (
                        <li key={link.id}>
                            <Link
                                href={show(link.slug)}
                                className="text-sm hover:underline"
                                data-test="note-link"
                            >
                                {link.title}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export function NoteLinksPanel({
    outgoingLinks,
    backlinks,
}: {
    outgoingLinks: NoteLink[];
    backlinks: NoteLink[];
}) {
    return (
        <>
            <LinkList title="Links to" links={outgoingLinks} testId="outgoing-links" />
            <LinkList title="Backlinks" links={backlinks} testId="backlinks" />
        </>
    );
}
