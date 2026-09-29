import 'katex/dist/katex.min.css';
import { EditorContent, useEditor } from '@tiptap/react';
import { useEffect, useId, useRef } from 'react';
import type { RichContent, RichNode } from '@/features/rich-content/types';
import { cn } from '@/lib/utils';
import type { EditMath } from './extensions';
import { editorExtensions } from './extensions';
import { PromptProvider, useAsk } from './prompt-dialog';
import { askMath } from './prompts';
import { Toolbar } from './toolbar';

export type RichContentEditorProps = {
    /** Initial document. The editor owns the content afterwards. */
    value: RichContent;
    onChange: (value: RichContent) => void;
    labelledBy: string;
    describedBy?: string;
    invalid?: boolean;
    /** Short fields (a track description) do not need a tall empty area. */
    compact?: boolean;
};

/**
 * TipTap editor limited to the RichContent allowlist (ADR-023). It emits
 * the same {version, doc} envelope the server validates and the reader
 * renders. `onChange` fires only on real edits, so opening a lesson and
 * saving it untouched does not rewrite its body.
 */
export default function RichContentEditor(props: RichContentEditorProps) {
    return (
        <PromptProvider>
            <Editor {...props} />
        </PromptProvider>
    );
}

function Editor({
    value,
    onChange,
    labelledBy,
    describedBy,
    invalid = false,
    compact = false,
}: RichContentEditorProps) {
    const ask = useAsk();
    const contentId = useId();
    const onChangeRef = useRef(onChange);
    const editMathRef = useRef<EditMath>(() => undefined);

    const editor = useEditor({
        extensions: editorExtensions({
            onEditMath: (kind, pos, latex) =>
                editMathRef.current(kind, pos, latex),
        }),
        content: value.doc,
        shouldRerenderOnTransaction: false,
        editorProps: {
            attributes: {
                id: contentId,
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-labelledby': labelledBy,
                ...(describedBy === undefined
                    ? {}
                    : { 'aria-describedby': describedBy }),
                class: `rich-content prose ${compact ? 'min-h-32' : 'min-h-80'} max-w-none px-4 py-3 focus:outline-none prose-code:rounded prose-code:bg-muted prose-code:px-1 prose-code:py-0.5 prose-code:font-normal prose-code:before:content-none prose-code:after:content-none sm:px-6`,
            },
        },
        onUpdate: ({ editor: current }) =>
            onChangeRef.current({
                version: 1,
                doc: current.getJSON() as RichNode,
            }),
    });

    useEffect(() => {
        onChangeRef.current = onChange;
        editMathRef.current = (kind, pos, latex) => {
            void askMath(ask, latex, kind === 'blockMath').then((next) => {
                const chain = editor.chain().focus();

                if (next === null) {
                    chain.run();
                } else if (kind === 'inlineMath') {
                    chain.updateInlineMath({ latex: next, pos }).run();
                } else {
                    chain.updateBlockMath({ latex: next, pos }).run();
                }
            });
        };
    });

    return (
        <div
            className={cn(
                'rounded-lg border bg-background shadow-xs',
                invalid && 'border-destructive',
            )}
        >
            <Toolbar editor={editor} controls={contentId} />
            <EditorContent editor={editor} />
        </div>
    );
}
