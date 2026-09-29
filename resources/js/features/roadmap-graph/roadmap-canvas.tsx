import '@xyflow/react/dist/style.css';
import {
    Background,
    BackgroundVariant,
    Controls,
    MarkerType,
    ReactFlow,
} from '@xyflow/react';
import type { Edge } from '@xyflow/react';
import { useMemo } from 'react';
import { useIsDarkMode } from '@/hooks/use-is-dark-mode';
import { t } from '@/i18n';
import { layoutGraph } from './layout';
import type { TrackFlowNode } from './track-node';
import { TrackNode } from './track-node';
import type { RoadmapEdge, RoadmapTrack } from './types';

const nodeTypes = { track: TrackNode };

type Props = {
    tracks: RoadmapTrack[];
    edges: RoadmapEdge[];
    selected: string | null;
    onOpen: (slug: string) => void;
};

/**
 * React Flow canvas (ADR-010). Loaded lazily by the roadmap page, so the
 * phone list view never downloads it. States come from the server.
 */
export default function RoadmapCanvas({
    tracks,
    edges,
    selected,
    onOpen,
}: Props) {
    const dark = useIsDarkMode();
    const ids = useMemo(() => tracks.map((track) => track.slug), [tracks]);
    const positions = useMemo(() => layoutGraph(ids, edges), [ids, edges]);

    const related = useMemo(() => {
        if (selected === null) {
            return null;
        }

        const set = new Set([selected]);
        edges.forEach((edge) => {
            if (edge.from === selected) set.add(edge.to);
            if (edge.to === selected) set.add(edge.from);
        });

        return set;
    }, [selected, edges]);

    const nodes: TrackFlowNode[] = tracks.map((track) => ({
        id: track.slug,
        type: 'track',
        position: positions[track.slug],
        draggable: false,
        selectable: false,
        focusable: false,
        data: {
            track,
            selected: track.slug === selected,
            dimmed: related !== null && !related.has(track.slug),
            onOpen,
        },
    }));

    const flowEdges: Edge[] = edges.map((edge) => {
        const highlighted =
            selected !== null &&
            (edge.from === selected || edge.to === selected);
        const color = highlighted
            ? 'var(--foreground)'
            : 'var(--muted-foreground)';

        return {
            id: `${edge.from}->${edge.to}`,
            source: edge.from,
            target: edge.to,
            type: 'smoothstep',
            focusable: false,
            markerEnd: {
                type: MarkerType.ArrowClosed,
                color,
                width: 16,
                height: 16,
            },
            style: {
                stroke: color,
                strokeWidth: highlighted ? 2.5 : 1.5,
                strokeDasharray:
                    edge.kind === 'RECOMMENDED' ? '6 4' : undefined,
                opacity: selected !== null && !highlighted ? 0.35 : 1,
            },
        };
    });

    return (
        <ReactFlow
            nodes={nodes}
            edges={flowEdges}
            nodeTypes={nodeTypes}
            colorMode={dark ? 'dark' : 'light'}
            fitView
            fitViewOptions={{ padding: 0.25, maxZoom: 1 }}
            minZoom={0.3}
            maxZoom={1.5}
            nodesDraggable={false}
            nodesConnectable={false}
            elementsSelectable={false}
            nodesFocusable={false}
            edgesFocusable={false}
            // Required: React Flow turns pointer events off on nodes that are
            // neither draggable, selectable nor clickable, and the pane then
            // swallows every click (the E2E caught it). The button inside the
            // node keeps Tab + Enter working.
            onNodeClick={(_, node) => onOpen(node.id)}
            aria-label={t('roadmap.canvasLabel')}
            ariaLabelConfig={{
                'controls.ariaLabel': t('roadmap.a11y.controls'),
                'controls.zoomIn.ariaLabel': t('roadmap.a11y.zoomIn'),
                'controls.zoomOut.ariaLabel': t('roadmap.a11y.zoomOut'),
                'controls.fitView.ariaLabel': t('roadmap.a11y.fitView'),
                'controls.interactive.ariaLabel': t('roadmap.a11y.interactive'),
                'minimap.ariaLabel': t('roadmap.a11y.minimap'),
                'node.a11yDescription.default': t('roadmap.a11y.node'),
                'node.a11yDescription.keyboardDisabled': t('roadmap.a11y.node'),
                'edge.a11yDescription.default': t('roadmap.a11y.edge'),
            }}
        >
            <Background variant={BackgroundVariant.Dots} gap={20} size={1} />
            <Controls showInteractive={false} />
        </ReactFlow>
    );
}
