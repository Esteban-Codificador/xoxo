import { mergeAttributes, Node } from '@tiptap/core';
import {
    NodeViewContent,
    NodeViewWrapper,
    ReactNodeViewRenderer,
} from '@tiptap/react';
import type { ReactNodeViewProps } from '@tiptap/react';
import {
    Callout as CalloutBox,
    calloutVariants,
    isCalloutVariant,
} from '@/features/rich-content/nodes/callout';
import { t } from '@/i18n';

function CalloutView({ node, updateAttributes }: ReactNodeViewProps) {
    const variant = isCalloutVariant(node.attrs.variant)
        ? node.attrs.variant
        : 'note';

    return (
        <NodeViewWrapper>
            <CalloutBox
                variant={variant}
                label={
                    <span contentEditable={false}>
                        <select
                            aria-label={t('editor.calloutVariant')}
                            value={variant}
                            onChange={(event) =>
                                updateAttributes({
                                    variant: event.target.value,
                                })
                            }
                            className="cursor-pointer rounded-md bg-transparent py-0.5 pr-1 font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            {calloutVariants.map((option) => (
                                <option key={option} value={option}>
                                    {t(`callout.${option}`)}
                                </option>
                            ))}
                        </select>
                    </span>
                }
            >
                <NodeViewContent className="[&>:first-child]:mt-0 [&>:last-child]:mb-0" />
            </CalloutBox>
        </NodeViewWrapper>
    );
}

/** `callout` (attrs: variant). Editable content, same box as the reader. */
export const Callout = Node.create({
    name: 'callout',
    group: 'block',
    content: 'block+',
    defining: true,

    addAttributes() {
        return {
            variant: {
                default: 'note',
                parseHTML: (element) => {
                    const variant = element.getAttribute('data-variant');

                    return isCalloutVariant(variant) ? variant : 'note';
                },
                renderHTML: (attributes) => ({
                    'data-variant': attributes.variant,
                }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'aside[data-callout]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'aside',
            mergeAttributes({ 'data-callout': '' }, HTMLAttributes),
            0,
        ];
    },

    addNodeView() {
        return ReactNodeViewRenderer(CalloutView);
    },
});
