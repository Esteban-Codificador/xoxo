import { mergeAttributes, Node } from '@tiptap/core';
import { ReactNodeViewRenderer } from '@tiptap/react';
import type { ReactNodeViewProps } from '@tiptap/react';
import { VideoEmbed } from '@/features/rich-content/nodes/video-embed';
import { t } from '@/i18n';
import { NodeFrame } from '../node-frame';
import { useAsk } from '../prompt-dialog';
import type { Ask } from '../prompt-dialog';
import { parseYouTubeId } from '../youtube';

/** Resolves with a YouTube video ID taken from what the editor pasted. */
export async function askVideoId(
    ask: Ask,
    initial = '',
): Promise<string | null> {
    const value = await ask({
        title: t('editor.prompts.videoTitle'),
        label: t('editor.prompts.videoLabel'),
        help: t('editor.prompts.videoHelp'),
        initial,
        validate: (input) =>
            parseYouTubeId(input) === null
                ? t('editor.prompts.videoInvalid')
                : null,
    });

    return value === null ? null : parseYouTubeId(value);
}

function VideoView({
    node,
    selected,
    updateAttributes,
    deleteNode,
    editor,
}: ReactNodeViewProps) {
    const ask = useAsk();
    const videoId = String(node.attrs.videoId ?? '');

    const edit = async () => {
        const next = await askVideoId(ask, videoId);

        if (next !== null) {
            updateAttributes({ provider: 'youtube', videoId: next });
        }

        editor.commands.focus();
    };

    return (
        <NodeFrame
            label={t('editor.videoCaption', { id: videoId })}
            selected={selected}
            onEdit={() => void edit()}
            onRemove={deleteNode}
        >
            <VideoEmbed provider={node.attrs.provider} videoId={videoId} />
        </NodeFrame>
    );
}

/** `video` (attrs: provider "youtube", videoId). Never an iframe in the data. */
export const Video = Node.create({
    name: 'video',
    group: 'block',
    atom: true,

    addAttributes() {
        return {
            provider: {
                default: 'youtube',
                parseHTML: () => 'youtube',
                renderHTML: () => ({ 'data-provider': 'youtube' }),
            },
            videoId: {
                default: '',
                parseHTML: (element) =>
                    element.getAttribute('data-video-id') ?? '',
                renderHTML: (attributes) => ({
                    'data-video-id': attributes.videoId,
                }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-video]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes({ 'data-video': '' }, HTMLAttributes)];
    },

    addNodeView() {
        return ReactNodeViewRenderer(VideoView);
    },
});
