import type { Root, Text } from 'mdast';
import type { Plugin } from 'unified';
import { visit } from 'unist-util-visit';

const WIKILINK = /\[\[([^\]]+)\]\]/g;

/**
 * Remark plugin that rewrites `[[Title]]` occurrences in text into nodes the
 * preview can render: a resolvable one becomes a `link` (whose url the renderer
 * turns into an Inertia <Link>), an unresolved one a marked node shown muted.
 * Resolution is deferred to the renderer via the title→slug map, so the plugin
 * itself only carries the raw title.
 */
export const remarkWikilink: Plugin<[], Root> = () => {
    return (tree: Root) => {
        visit(tree, 'text', (node: Text, index, parent) => {
            if (!parent || index === undefined || !WIKILINK.test(node.value)) {
                return;
            }

            WIKILINK.lastIndex = 0;
            const children: Array<Text | LinkNode> = [];
            let last = 0;
            let match: RegExpExecArray | null;

            while ((match = WIKILINK.exec(node.value)) !== null) {
                if (match.index > last) {
                    children.push({ type: 'text', value: node.value.slice(last, match.index) });
                }

                const title = match[1];
                children.push({
                    type: 'link',
                    url: `#wikilink`,
                    data: { hProperties: { 'data-wikilink': title } },
                    children: [{ type: 'text', value: title }],
                });

                last = match.index + match[0].length;
            }

            if (last < node.value.length) {
                children.push({ type: 'text', value: node.value.slice(last) });
            }

            parent.children.splice(index, 1, ...(children as Text[]));
        });
    };
};

type LinkNode = {
    type: 'link';
    url: string;
    data: { hProperties: { 'data-wikilink': string } };
    children: Text[];
};
