import type { CompletionSource } from '@codemirror/autocomplete';
import type { Extension } from '@codemirror/state';
import type { EditorView } from '@codemirror/view';
import { useEffect, useRef } from 'react';

type Props = {
    value: string;
    onChange: (value: string) => void;
    /** Extra CodeMirror extensions. */
    extensions?: Extension[];
    /** Autocomplete source, e.g. the [[wikilink]] source. */
    completionSource?: CompletionSource;
    placeholder?: string;
    ariaLabel?: string;
    minHeight?: string;
};

const NO_EXTENSIONS: Extension[] = [];

/**
 * A markdown *source* editor backed by CodeMirror 6, loaded lazily so the index
 * page never pays for the editor bundle. Keeps the body as plain markdown so the
 * server-side `[[wikilink]]` parsing stays intact.
 */
export function MarkdownEditor({
    value,
    onChange,
    extensions = NO_EXTENSIONS,
    completionSource,
    placeholder,
    ariaLabel,
    minHeight = '20rem',
}: Props) {
    const host = useRef<HTMLDivElement>(null);
    const viewRef = useRef<EditorView | null>(null);
    // Keep the latest onChange reachable from the CM update listener without
    // rebuilding the editor on every render.
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        let view: EditorView | null = null;
        let disposed = false;

        (async () => {
            const [{ EditorState }, cmView, { markdown }, { defaultKeymap, history, historyKeymap }, autocomplete] =
                await Promise.all([
                    import('@codemirror/state'),
                    import('@codemirror/view'),
                    import('@codemirror/lang-markdown'),
                    import('@codemirror/commands'),
                    import('@codemirror/autocomplete'),
                ]);

            if (disposed || !host.current) {
                return;
            }

            const { EditorView, keymap, placeholder: cmPlaceholder } = cmView;
            const { autocompletion, completionKeymap } = autocomplete;

            const completion: Extension[] = completionSource
                ? [
                      autocompletion({ override: [completionSource], closeOnBlur: false }),
                      keymap.of(completionKeymap),
                  ]
                : [];

            const updateListener = EditorView.updateListener.of((v) => {
                if (v.docChanged) {
                    onChangeRef.current(v.state.doc.toString());
                }
            });

            view = new EditorView({
                parent: host.current,
                state: EditorState.create({
                    doc: value,
                    extensions: [
                        // Completion first so its keymap (Enter accepts, esc closes)
                        // out-ranks the default Enter=newline binding while the popup
                        // is open.
                        ...completion,
                        history(),
                        keymap.of([...defaultKeymap, ...historyKeymap]),
                        markdown(),
                        EditorView.lineWrapping,
                        updateListener,
                        placeholder ? cmPlaceholder(placeholder) : [],
                        EditorView.theme({
                            '&': { minHeight },
                            '.cm-content': { fontFamily: 'ui-monospace, monospace', minHeight },
                            '&.cm-focused': { outline: 'none' },
                        }),
                        ...extensions,
                    ],
                }),
            });

            if (ariaLabel) {
                view.contentDOM.setAttribute('aria-label', ariaLabel);
            }

            viewRef.current = view;
        })();

        return () => {
            disposed = true;
            view?.destroy();
            viewRef.current = null;
        };
        // Rebuild only when the extension set or completion source identity changes.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [extensions, completionSource]);

    // Reconcile external value changes (e.g. a partial reload) into the editor
    // without clobbering the caret while the user is typing.
    useEffect(() => {
        const view = viewRef.current;

        if (!view) {
            return;
        }

        const current = view.state.doc.toString();

        if (current !== value) {
            view.dispatch({
                changes: { from: 0, to: current.length, insert: value },
            });
        }
         
    }, [value]);

    return <div ref={host} data-test="markdown-editor" className="rounded-md border" />;
}
