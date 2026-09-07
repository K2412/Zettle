<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { marked } from 'marked';
    import { show } from '@/routes/notes';

    let {
        body,
        titleToSlug = {},
    }: {
        body: string;
        titleToSlug?: Record<string, string>;
    } = $props();

    const html = $derived.by(() => {
        if (body.trim() === '') {
            return '';
        }

        const withLinks = body.replace(/\[\[([^\]]+)\]\]/g, (_match, title: string) => {
            const slug = titleToSlug[title];

            if (slug) {
                const href = show(slug).url;

                return `<a href="${href}" data-test="wikilink-resolved">${escapeHtml(title)}</a>`;
            }

            return `<span class="text-muted-foreground underline decoration-dotted" data-test="wikilink-unresolved" title="No note with this title yet">${escapeHtml(title)}</span>`;
        });

        return marked.parse(withLinks, { async: false }) as string;
    });

    function escapeHtml(value: string): string {
        return value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function onClick(event: MouseEvent) {
        const anchor = (event.target as HTMLElement).closest('a');

        if (!anchor || !anchor.getAttribute('href')) {
            return;
        }

        event.preventDefault();
        router.visit(anchor.getAttribute('href') as string);
    }

    function intercept(node: HTMLElement) {
        node.addEventListener('click', onClick);

        return {
            destroy() {
                node.removeEventListener('click', onClick);
            },
        };
    }
</script>

<div
    class="prose prose-sm max-w-none rounded-md border p-4 dark:prose-invert"
    data-test="note-preview"
    use:intercept
>
    {#if body.trim()}
        {@html html}
    {:else}
        <p class="text-muted-foreground">Nothing to preview yet.</p>
    {/if}
</div>
