import { Graph, layout } from '@dagrejs/dagre';

export type LayoutEdge = { from: string; to: string };
export type Position = { x: number; y: number };

export const NODE_WIDTH = 272;
export const NODE_HEIGHT = 128;

/**
 * Top-left positions for every node, prerequisites above what they unlock
 * (dagre, top to bottom). A pure function: new content from the CMS never
 * needs nodes placed by hand.
 */
export function layoutGraph(
    ids: string[],
    edges: LayoutEdge[],
): Record<string, Position> {
    const graph = new Graph();
    graph.setGraph({
        rankdir: 'TB',
        nodesep: 48,
        ranksep: 80,
        marginx: 16,
        marginy: 16,
    });
    graph.setDefaultEdgeLabel(() => ({}));

    for (const id of ids) {
        graph.setNode(id, { width: NODE_WIDTH, height: NODE_HEIGHT });
    }

    for (const edge of edges) {
        if (graph.hasNode(edge.from) && graph.hasNode(edge.to)) {
            graph.setEdge(edge.from, edge.to);
        }
    }

    layout(graph);

    return Object.fromEntries(
        ids.map((id) => {
            const node = graph.node(id);

            return [
                id,
                { x: node.x - NODE_WIDTH / 2, y: node.y - NODE_HEIGHT / 2 },
            ];
        }),
    );
}

/**
 * Level of each node for the list view: 1 for starting points, otherwise
 * one more than its deepest prerequisite (longest path). Order within a
 * level follows `ids`.
 */
export function computeLevels(ids: string[], edges: LayoutEdge[]): string[][] {
    const known = new Set(ids);
    const incoming = new Map(ids.map((id) => [id, [] as string[]]));

    for (const edge of edges) {
        if (known.has(edge.from) && known.has(edge.to)) {
            incoming.get(edge.to)?.push(edge.from);
        }
    }

    const levels = new Map<string, number>();
    const visiting = new Set<string>();

    const levelOf = (id: string): number => {
        const cached = levels.get(id);

        if (cached !== undefined) {
            return cached;
        }

        // The server rejects cycles; this only keeps a bad edge from looping.
        if (visiting.has(id)) {
            return 1;
        }

        visiting.add(id);
        const level = 1 + Math.max(0, ...(incoming.get(id) ?? []).map(levelOf));
        visiting.delete(id);
        levels.set(id, level);

        return level;
    };

    const grouped: string[][] = [];

    for (const id of ids) {
        const level = levelOf(id);
        (grouped[level - 1] ??= []).push(id);
    }

    return grouped.filter((group) => group !== undefined);
}
