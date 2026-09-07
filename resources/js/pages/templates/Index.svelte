<script module lang="ts">
    import { index } from '@/routes/templates';

    export const layout = {
        breadcrumbs: [{ title: 'Templates', href: index() }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import NoteTemplateController from '@/actions/App/Http/Controllers/NoteTemplateController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { NoteTemplate, Tag } from '@/types';

    let {
        templates,
        tags,
    }: {
        templates: NoteTemplate[];
        tags: Tag[];
    } = $props();

    let editing = $state<NoteTemplate | null>(null);

    function selectedTagIds(template: NoteTemplate | null): Set<number> {
        return new Set((template?.tags ?? []).map((tag) => tag.id));
    }
</script>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
    <AppHead title="Templates" />

    <Heading title="Templates" />

    {#if editing}
        <Form
            {...NoteTemplateController.update.form(editing.id)}
            options={{ preserveScroll: true }}
            resetOnSuccess
            onSuccess={() => (editing = null)}
            class="flex flex-col gap-3 rounded-lg border p-5"
            data-test="template-form"
        >
            {#snippet children({ processing, errors })}
                <h2 class="text-base font-medium">Edit template</h2>
                {@render templateFields(editing, errors)}
                <div class="flex items-center gap-2">
                    <Button type="submit" disabled={processing}>Update</Button>
                    <Button type="button" variant="ghost" onclick={() => (editing = null)}>Cancel</Button>
                </div>
            {/snippet}
        </Form>
    {:else}
        <Form
            {...NoteTemplateController.store.form()}
            options={{ preserveScroll: true }}
            resetOnSuccess
            class="flex flex-col gap-3 rounded-lg border p-5"
            data-test="template-form"
        >
            {#snippet children({ processing, errors })}
                <h2 class="text-base font-medium">New template</h2>
                {@render templateFields(null, errors)}
                <div>
                    <Button type="submit" disabled={processing}>Create</Button>
                </div>
            {/snippet}
        </Form>
    {/if}

    <div class="flex flex-col gap-2">
        {#if templates.length === 0}
            <div
                class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                data-test="templates-empty"
            >
                No templates yet. Create one above to scaffold new notes.
            </div>
        {:else}
            {#each templates as template (template.id)}
                <div
                    class="flex items-center justify-between gap-3 rounded-lg border p-4"
                    data-test="template-row"
                >
                    <div class="flex flex-1 flex-col gap-1">
                        <h3 class="text-base font-medium">{template.name}</h3>
                        <div class="flex flex-wrap gap-2">
                            {#if template.hotkey}
                                <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs">
                                    {template.hotkey}
                                </span>
                            {/if}
                            {#if template.title_prefix}
                                <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs">
                                    {template.title_prefix}
                                </span>
                            {/if}
                            {#each template.tags ?? [] as tag (tag.id)}
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs"
                                    style={`background-color: ${tag.color}20; color: ${tag.color}`}
                                >
                                    {tag.name}
                                </span>
                            {/each}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Form {...NoteTemplateController.apply.form(template.id)}>
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                    data-test="template-apply"
                                >
                                    Use
                                </Button>
                            {/snippet}
                        </Form>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onclick={() => (editing = template)}
                            data-test="template-edit"
                        >
                            Edit
                        </Button>
                        <Form
                            {...NoteTemplateController.destroy.form(template.id)}
                            options={{ preserveScroll: true }}
                        >
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    size="sm"
                                    variant="destructive"
                                    disabled={processing}
                                    data-test="template-delete"
                                >
                                    Delete
                                </Button>
                            {/snippet}
                        </Form>
                    </div>
                </div>
            {/each}
        {/if}
    </div>
</div>

{#snippet templateFields(template: NoteTemplate | null, errors: Record<string, string>)}
    {@const selected = selectedTagIds(template)}
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="template-name">Name</Label>
            <Input id="template-name" name="name" value={template?.name ?? ''} required />
            <InputError message={errors.name} />
        </div>
        <div class="grid gap-2">
            <Label for="template-hotkey">Hotkey (e.g. ctrl+shift+f)</Label>
            <Input id="template-hotkey" name="hotkey" value={template?.hotkey ?? ''} />
            <InputError message={errors.hotkey} />
        </div>
    </div>
    <div class="grid gap-2">
        <Label for="template-title-prefix">Title prefix</Label>
        <Input
            id="template-title-prefix"
            name="title_prefix"
            value={template?.title_prefix ?? ''}
        />
        <InputError message={errors.title_prefix} />
    </div>
    <div class="grid gap-2">
        <Label for="template-body">Body template</Label>
        <textarea
            id="template-body"
            name="body_template"
            rows="6"
            value={template?.body_template ?? ''}
            class="rounded-md border bg-transparent p-3 text-sm shadow-sm"
        ></textarea>
        <InputError message={errors.body_template} />
    </div>
    {#if tags.length > 0}
        <div class="grid gap-2">
            <Label>Auto-apply tags</Label>
            <div class="mt-1 flex flex-wrap gap-2">
                {#each tags as tag (tag.id)}
                    <label class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-sm">
                        <input
                            type="checkbox"
                            name="tag_ids[]"
                            value={tag.id}
                            checked={selected.has(tag.id)}
                        />
                        <span class="size-2 rounded-full" style={`background-color: ${tag.color}`}></span>
                        {tag.name}
                    </label>
                {/each}
            </div>
        </div>
    {/if}
{/snippet}
