import { mergeAttributes, Node } from '@tiptap/core';
import { ReactNodeViewRenderer } from '@tiptap/react';
import type { ReactNodeViewProps } from '@tiptap/react';
import { ContentImage } from '@/features/rich-content/nodes/content-image';
import { t } from '@/i18n';
import { NodeFrame } from '../node-frame';
import { useAsk } from '../prompt-dialog';
import type { Ask } from '../prompt-dialog';

/** Mirrors RichContentSchema::MAX_ALT_LENGTH. */
export const MAX_ALT_LENGTH = 300;

/** Returns an error message, or null when the text is acceptable. */
export function altTextError(value: string): string | null {
    const text = value.trim();

    if (text === '') {
        return t('editor.image.altRequired');
    }

    return text.length > MAX_ALT_LENGTH ? t('editor.image.altTooLong') : null;
}

export async function askAltText(
    ask: Ask,
    initial: string,
): Promise<string | null> {
    const value = await ask({
        title: t('editor.image.altTitle'),
        label: t('editor.image.altLabel'),
        help: t('editor.image.altHelp'),
        initial,
        validate: altTextError,
    });

    return value === null ? null : value.trim();
}

function mediaIdOf(element: HTMLElement): number | null {
    const id = Number(element.getAttribute('data-media-id'));

    return Number.isInteger(id) && id > 0 ? id : null;
}

function ImageView({
    node,
    selected,
    updateAttributes,
    deleteNode,
    editor,
}: ReactNodeViewProps) {
    const ask = useAsk();
    const alt = String(node.attrs.alt ?? '');

    const edit = async () => {
        const next = await askAltText(ask, alt);

        if (next !== null) {
            updateAttributes({ alt: next });
        }

        editor.commands.focus();
    };

    return (
        <NodeFrame
            label={t('editor.image.caption', { alt })}
            editLabel={t('editor.image.editAlt')}
            selected={selected}
            onEdit={() => void edit()}
            onRemove={deleteNode}
        >
            <ContentImage mediaId={node.attrs.mediaId} alt={alt} />
        </NodeFrame>
    );
}

/**
 * `image` (attrs: mediaId, alt). Only images uploaded to the platform:
 * pasted HTML keeps an image only if it carries a media id.
 */
export const Image = Node.create({
    name: 'image',
    group: 'block',
    atom: true,

    addAttributes() {
        return {
            mediaId: {
                default: null,
                parseHTML: mediaIdOf,
                renderHTML: (attributes) => ({
                    'data-media-id': attributes.mediaId,
                }),
            },
            alt: {
                default: '',
                parseHTML: (element) => element.getAttribute('alt') ?? '',
                renderHTML: (attributes) => ({ alt: attributes.alt }),
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'img[data-media-id]',
                getAttrs: (element) =>
                    mediaIdOf(element) === null ? false : null,
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['img', mergeAttributes(HTMLAttributes)];
    },

    addNodeView() {
        return ReactNodeViewRenderer(ImageView);
    },
});
