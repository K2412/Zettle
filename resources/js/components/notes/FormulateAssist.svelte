<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { evaluateDraft } from '@/lib/note-assists';
    import { FORMULATE_TEMPLATES } from '@/lib/formulate-templates';
    import type { Note } from '@/types';

    let { note }: { note: Note } = $props();

    let selectedType = $state<string | null>(null);
    let draft = $state('');
    let critique = $state('');
    let evalState = $state<'idle' | 'loading' | 'ready'>('idle');

    const selected = $derived(
        FORMULATE_TEMPLATES.find((template) => template.type === selectedType) ?? null,
    );

    async function evaluate() {
        evalState = 'loading';
        critique = await evaluateDraft(note.slug, draft);
        evalState = 'ready';
    }
</script>

<div class="flex flex-col gap-4" data-test="formulate-assist">
    <p class="text-sm text-muted-foreground">
        Reach for a scaffold that fits the shape of the idea, or paste a draft below for a critique.
    </p>

    <div class="flex flex-wrap gap-1.5" role="tablist">
        {#each FORMULATE_TEMPLATES as template (template.type)}
            <button
                type="button"
                role="tab"
                aria-selected={template.type === selectedType}
                onclick={() => (selectedType = template.type)}
                data-test={`formulate-template-${template.type}`}
                class={template.type === selectedType
                    ? 'rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground'
                    : 'rounded-md border px-2 py-1 text-xs text-muted-foreground hover:text-foreground'}
            >
                {template.label}
            </button>
        {/each}
    </div>

    {#if selected}
        <div class="flex flex-col gap-2" data-test="formulate-template">
            <pre class="overflow-x-auto whitespace-pre-wrap rounded-xl border bg-muted/40 p-4 text-sm">
{selected.body}</pre>
            <div>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onclick={() => navigator.clipboard.writeText(selected.body)}
                    data-test="formulate-copy-template"
                >
                    Copy template
                </Button>
            </div>
        </div>
    {/if}

    <div class="flex flex-col gap-2">
        <label class="text-xs font-medium text-muted-foreground" for="formulate-draft">
            Your draft
        </label>
        <textarea
            id="formulate-draft"
            bind:value={draft}
            rows="6"
            placeholder="Write or paste your draft to get a critique…"
            data-test="formulate-draft"
            class="rounded-xl border bg-transparent p-3 text-sm shadow-none focus-visible:ring-0"
        ></textarea>
        <div>
            <Button
                type="button"
                size="sm"
                variant="outline"
                onclick={evaluate}
                disabled={evalState === 'loading' || draft.trim() === ''}
                data-test="formulate-evaluate"
            >
                {evalState === 'loading' ? 'Evaluating…' : 'Evaluate draft'}
            </Button>
        </div>
    </div>

    {#if evalState === 'loading'}
        <div class="h-20 animate-pulse rounded-md bg-muted" data-test="formulate-evaluating"></div>
    {/if}

    {#if evalState === 'ready' && critique !== ''}
        <div class="flex flex-col gap-2 rounded-xl border p-4" data-test="formulate-critique">
            <span class="text-xs font-medium text-muted-foreground">Critique</span>
            <p class="whitespace-pre-wrap text-sm text-muted-foreground">{critique}</p>
            <div>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onclick={() => navigator.clipboard.writeText(critique)}
                    data-test="formulate-copy-critique"
                >
                    Copy suggestions
                </Button>
            </div>
        </div>
    {/if}
</div>
