import { Handle, Position } from '@xyflow/react';
import type { Node, NodeProps } from '@xyflow/react';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { NODE_HEIGHT, NODE_WIDTH } from './layout';
import type { RoadmapTrack } from './types';

export type TrackNodeData = {
    track: RoadmapTrack;
    selected: boolean;
    dimmed: boolean;
    onOpen: (slug: string) => void;
};

export type TrackFlowNode = Node<TrackNodeData, 'track'>;

/**
 * A track on the canvas: a real button (Tab + Enter open the panel), with
 * the state as icon and text, and the progress bar.
 */
export function TrackNode({ data }: NodeProps<TrackFlowNode>) {
    const { track, selected, dimmed, onOpen } = data;

    return (
        <>
            <Handle
                type="target"
                position={Position.Top}
                isConnectable={false}
                className="!border-0 !bg-transparent"
            />
            <button
                type="button"
                onClick={() => onOpen(track.slug)}
                aria-label={t('roadmap.openDetails', { track: track.title })}
                aria-pressed={selected}
                style={{ width: NODE_WIDTH, height: NODE_HEIGHT }}
                className={cn(
                    'nodrag nopan flex flex-col gap-2 rounded-xl border bg-card p-4 text-left shadow-xs transition',
                    'hover:border-foreground/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    selected && 'border-foreground ring-1 ring-foreground',
                    dimmed && 'opacity-40',
                )}
            >
                <span className="flex items-start justify-between gap-2">
                    <span className="font-mono text-xs text-muted-foreground tabular-nums">
                        {String(track.position).padStart(2, '0')}
                    </span>
                    <StateBadge state={track.progress.state} />
                </span>
                <span className="line-clamp-2 leading-snug font-medium">
                    {track.title}
                </span>
                <span className="mt-auto flex items-center gap-2">
                    <ProgressBar
                        value={track.progress.progress}
                        state={
                            track.progress.state === 'COMPLETED'
                                ? 'COMPLETED'
                                : 'IN_PROGRESS'
                        }
                        className="flex-1"
                    />
                    <span className="text-xs text-muted-foreground tabular-nums">
                        {track.progress.completed}/{track.progress.total}
                    </span>
                </span>
            </button>
            <Handle
                type="source"
                position={Position.Bottom}
                isConnectable={false}
                className="!border-0 !bg-transparent"
            />
        </>
    );
}
