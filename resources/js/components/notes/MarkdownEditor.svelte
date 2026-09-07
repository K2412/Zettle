<script lang="ts">
    import type { CompletionSource } from '@codemirror/autocomplete';
    import type { Extension } from '@codemirror/state';
    import type { EditorView as EditorViewType } from '@codemirror/view';
    import { untrack } from 'svelte';

    let {
        value,
        onChange,
        completionSource,
        placeholder = '',
        ariaLabel = '',
        minHeight = '20rem',
    }: {
        value: string;
        onChange: (value: string) => void;
        completionSource?: CompletionSource;
        placeholder?: string;
        ariaLabel?: string;
        minHeight?: string;
    } = $props();

    let host: HTMLDivElement | undefined = $state();
    let view: EditorViewType | null = null;
    const onChangeRef: { current: (value: string) => void } = { current: () => {} };

    $effect(() => {
        onChangeRef.current = onChange;
    });

    $effect(() => {
        const source = completionSource;
        const ph = placeholder;
        const label = ariaLabel;
        const height = minHeight;
        const hostEl = host;
        const initialDoc = untrack(() => value);

        if (!hostEl) {
            return;
        }

        let disposed = false;
        let localView: EditorViewType | null = null;

        void (async () => {
            const [
                { EditorState },
                cmView,
                { markdown },
                { defaultKeymap, history, historyKeymap },
                autocomplete,
            ] = await Promise.all([
                import('@codemirror/state'),
                import('@codemirror/view'),
                import('@codemirror/lang-markdown'),
                import('@codemirror/commands'),
                import('@codemirror/autocomplete'),
            ]);

            if (disposed || !hostEl) {
                return;
            }

            const { EditorView, keymap, placeholder: cmPlaceholder } = cmView;
            const { autocompletion, completionKeymap } = autocomplete;

            const completion: Extension[] = source
                ? [
                      autocompletion({ override: [source], closeOnBlur: false }),
                      keymap.of(completionKeymap),
                  ]
                : [];

            const updateListener = EditorView.updateListener.of((v) => {
                if (v.docChanged) {
                    onChangeRef.current(v.state.doc.toString());
                }
            });

            localView = new EditorView({
                parent: hostEl,
                state: EditorState.create({
                    doc: initialDoc,
                    extensions: [
                        ...completion,
                        history(),
                        keymap.of([...defaultKeymap, ...historyKeymap]),
                        markdown(),
                        EditorView.lineWrapping,
                        updateListener,
                        ph ? cmPlaceholder(ph) : [],
                        EditorView.theme({
                            '&': { minHeight: height },
                            '.cm-content': {
                                fontFamily: 'ui-monospace, monospace',
                                minHeight: height,
                            },
                            '&.cm-focused': { outline: 'none' },
                        }),
                    ],
                }),
            });

            if (label) {
                localView.contentDOM.setAttribute('aria-label', label);
            }

            view = localView;
        })();

        return () => {
            disposed = true;
            localView?.destroy();
            view = null;
        };
    });

    $effect(() => {
        const currentView = view;

        if (!currentView) {
            return;
        }

        const current = currentView.state.doc.toString();

        if (current !== value) {
            currentView.dispatch({
                changes: { from: 0, to: current.length, insert: value },
            });
        }
    });
</script>

<div bind:this={host} data-test="markdown-editor" class="rounded-md border"></div>
