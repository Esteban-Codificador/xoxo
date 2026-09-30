import type { Extensions } from '@tiptap/core';
import { CodeBlock } from '@tiptap/extension-code-block';
import { Link } from '@tiptap/extension-link';
import { Mathematics } from '@tiptap/extension-mathematics';
import { TableCell, TableHeader, TableKit } from '@tiptap/extension-table';
import { StarterKit } from '@tiptap/starter-kit';
import { safeHref } from '@/features/rich-content/safe-href';
import { Callout } from './nodes/callout';
import { Diagram } from './nodes/diagram';
import { Image } from './nodes/image';
import { Video } from './nodes/video';

export type MathKind = 'inlineMath' | 'blockMath';

export type EditMath = (kind: MathKind, pos: number, latex: string) => void;

const CODE_LANGUAGE = /^[a-z0-9+#-]{1,20}$/;

/** Mirrors RichContentSchema::CODE_LANGUAGE_PATTERN; anything else is dropped. */
export function normalizeLanguage(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null;
    }

    const language = value.trim().toLowerCase();

    return CODE_LANGUAGE.test(language) ? language : null;
}

/** Pasted `class="language-JavaScript"` becomes "javascript" (or nothing). */
const SafeCodeBlock = CodeBlock.extend({
    addAttributes() {
        return {
            language: {
                default: null,
                rendered: false,
                parseHTML: (element) => {
                    const className = [
                        ...(element.firstElementChild?.classList ?? []),
                    ].find((name) => name.startsWith('language-'));

                    return normalizeLanguage(
                        className?.slice('language-'.length),
                    );
                },
            },
        };
    },
});

/**
 * TipTap 3.31 added `align` to table cells; RichContent has no alignment,
 * so the attribute is dropped rather than rejected on save.
 */
function withoutAlign<T extends typeof TableCell | typeof TableHeader>(
    node: T,
): T {
    return node.extend({
        addAttributes() {
            return Object.fromEntries(
                Object.entries(this.parent?.() ?? {}).filter(
                    ([name]) => name !== 'align',
                ),
            );
        },
    }) as T;
}

/**
 * Only the attributes the server accepts (no "title"). target, rel and
 * class are never taken from pasted HTML: the reader decides how a link
 * opens from its href.
 */
const SafeLink = Link.extend({
    addAttributes() {
        return {
            href: {
                default: null,
                parseHTML: (element) => element.getAttribute('href'),
            },
            target: { default: null, parseHTML: () => null },
            rel: { default: null, parseHTML: () => null },
            class: { default: null, parseHTML: () => null },
        };
    },
}).configure({
    openOnClick: false,
    autolink: true,
    linkOnPaste: true,
    defaultProtocol: 'https',
    isAllowedUri: (url) => safeHref(url) !== null,
    HTMLAttributes: { target: null, rel: null, class: null },
});

/**
 * The editor's schema is exactly the RichContent allowlist (ADR-023, TD-5):
 * rich-content-editor.test.ts compares it with allowlist.json, which Pest
 * compares with RichContentSchema. Adding an extension here without adding
 * it to the server fails both tests.
 */
export function editorExtensions({
    onEditMath,
}: {
    onEditMath: EditMath;
}): Extensions {
    return [
        StarterKit.configure({
            heading: { levels: [2, 3, 4] },
            underline: false,
            link: false,
            codeBlock: false,
        }),
        SafeCodeBlock,
        SafeLink,
        TableKit.configure({
            table: { resizable: false },
            tableCell: false,
            tableHeader: false,
        }),
        withoutAlign(TableCell),
        withoutAlign(TableHeader),
        Mathematics.configure({
            katexOptions: {
                throwOnError: false,
                trust: false,
                strict: 'ignore',
                maxExpand: 500,
            },
            inlineOptions: {
                onClick: (node, pos) =>
                    onEditMath('inlineMath', pos, String(node.attrs.latex)),
            },
            blockOptions: {
                onClick: (node, pos) =>
                    onEditMath('blockMath', pos, String(node.attrs.latex)),
            },
        }),
        Callout,
        Diagram,
        Video,
        Image,
    ];
}
