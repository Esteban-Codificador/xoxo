import { mergeAttributes, Node } from '@tiptap/core';
import { ReactNodeViewRenderer } from '@tiptap/react';
import type { ReactNodeViewProps } from '@tiptap/react';
import { MermaidDiagram } from '@/features/rich-content/nodes/mermaid-diagram';
import { t } from '@/i18n';
import { NodeFrame } from '../node-frame';
import { useAsk } from '../prompt-dialog';
import type { Ask } from '../prompt-dialog';

export function askDiagram(ask: Ask, initial = ''): Promise<string | null> {
    return ask({
        title: t('editor.prompts.diagramTitle'),
        label: t('editor.prompts.diagramLabel'),
        help: t('editor.prompts.diagramHelp'),
        initial,
        multiline: true,
        validate: (value) =>
            value.trim() === '' ? t('editor.prompts.diagramEmpty') : null,
    });
}

function DiagramView({
    node,
    selected,
    updateAttributes,
    deleteNode,
    editor,
}: ReactNodeViewProps) {
    const ask = useAsk();
    const source = String(node.attrs.source ?? '');

    const edit = async () => {
        const next = await askDiagram(ask, source);

        if (next !== null) {
            updateAttributes({ source: next });
        }

        editor.commands.focus();
    };

    return (
        <NodeFrame
            label={t('editor.diagramCaption')}
            selected={selected}
            onEdit={() => void edit()}
            onRemove={deleteNode}
        >
            <MermaidDiagram source={source} />
        </NodeFrame>
    );
}

/** `diagram` (attrs: kind "mermaid", source). Stored as source, drawn on read. */
export const Diagram = Node.create({
    name: 'diagram',
    group: 'block',
    atom: true,

    addAttributes() {
        return {
            kind: {
                default: 'mermaid',
                parseHTML: () => 'mermaid',
                renderHTML: () => ({ 'data-kind': 'mermaid' }),
            },
            source: {
                default: '',
                parseHTML: (element) =>
                    element.getAttribute('data-source') ?? '',
                renderHTML: (attributes) => ({
                    'data-source': attributes.source,
                }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'figure[data-diagram]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'figure',
            mergeAttributes({ 'data-diagram': '' }, HTMLAttributes),
        ];
    },

    addNodeView() {
        return ReactNodeViewRenderer(DiagramView);
    },
});
