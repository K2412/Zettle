import { Link } from '@inertiajs/react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { remarkWikilink } from '@/lib/remark-wikilink';
import { show } from '@/routes/notes';

type Props = {
    body: string;
    /** title→slug map for resolving [[wikilinks]]. */
    titleToSlug?: Record<string, string>;
};

/**
 * Renders a note body as markdown. `[[wikilinks]]` are rewritten by the
 * remark-wikilink plugin and resolved here against the title→slug map: a match
 * becomes an Inertia <Link> that navigates; an unresolved one renders muted.
 */
export function NotePreview({ body, titleToSlug = {} }: Props) {
    return (
        <div
            className="prose prose-sm dark:prose-invert max-w-none rounded-md border p-4"
            data-test="note-preview"
        >
            {body.trim() ? (
                <Markdown
                    remarkPlugins={[remarkGfm, remarkWikilink]}
                    components={{
                        a({ href, children, ...props }) {
                            const title =
                                (props as Record<string, unknown>)['data-wikilink'] as string | undefined;

                            if (title === undefined) {
                                return (
                                    <a href={href} {...props}>
                                        {children}
                                    </a>
                                );
                            }

                            const slug = titleToSlug[title];

                            if (slug) {
                                return (
                                    <Link href={show(slug)} data-test="wikilink-resolved">
                                        {children}
                                    </Link>
                                );
                            }

                            return (
                                <span
                                    className="text-muted-foreground underline decoration-dotted"
                                    data-test="wikilink-unresolved"
                                    title="No note with this title yet"
                                >
                                    {children}
                                </span>
                            );
                        },
                    }}
                >
                    {body}
                </Markdown>
            ) : (
                <p className="text-muted-foreground">Nothing to preview yet.</p>
            )}
        </div>
    );
}
