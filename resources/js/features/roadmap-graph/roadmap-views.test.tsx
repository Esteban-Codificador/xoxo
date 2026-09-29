import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { NodeDetailPanel } from './node-detail-panel';
import { RoadmapListView } from './roadmap-list-view';
import type { RoadmapEdge, RoadmapTrack } from './types';

const track = (
    slug: string,
    title: string,
    overrides: Partial<RoadmapTrack> = {},
): RoadmapTrack => ({
    slug,
    title,
    summary: `Resumen de ${title}`,
    position: 0,
    difficulty: 'BEGINNER',
    estimated_hours: 3,
    lessons_count: 2,
    progress: {
        state: 'AVAILABLE',
        progress: 0,
        completed: 0,
        total: 2,
        blockers: [],
    },
    lessons: [],
    continue: null,
    ...overrides,
});

const tracks = [
    track('base', 'Base', {
        progress: {
            state: 'IN_PROGRESS',
            progress: 50,
            completed: 1,
            total: 2,
            blockers: [],
        },
        lessons: [
            {
                slug: 'primera',
                title: 'Primera',
                module_slug: 'intro',
                module_title: 'Introducción',
                state: 'COMPLETED',
            },
            {
                slug: 'segunda',
                title: 'Segunda',
                module_slug: 'intro',
                module_title: 'Introducción',
                state: 'AVAILABLE',
            },
        ],
        continue: { slug: 'segunda', title: 'Segunda' },
    }),
    track('siguiente', 'Siguiente', {
        position: 1,
        progress: {
            state: 'LOCKED',
            progress: 0,
            completed: 0,
            total: 1,
            blockers: [
                {
                    type: 'track',
                    slug: 'base',
                    title: 'Base',
                    progress: 50,
                    required: 100,
                },
            ],
        },
    }),
];

const edges: RoadmapEdge[] = [
    { from: 'base', to: 'siguiente', kind: 'REQUIRED', min_progress: 100 },
];

describe('RoadmapListView', () => {
    it('groups tracks by level and says what each one requires', async () => {
        const onOpen = vi.fn();
        render(
            <RoadmapListView
                roadmapSlug="ai-engineer"
                tracks={tracks}
                edges={edges}
                onOpen={onOpen}
            />,
        );

        const levels = screen.getAllByRole('heading', { level: 2 });
        expect(levels.map((heading) => heading.textContent)).toEqual([
            'Nivel 1',
            'Nivel 2',
        ]);
        expect(screen.getByRole('link', { name: 'Siguiente' })).toHaveAttribute(
            'href',
            '/roadmaps/ai-engineer/tracks/siguiente',
        );
        expect(
            screen.getByText('Necesita: Base (necesario)'),
        ).toBeInTheDocument();

        await userEvent.click(
            screen.getByRole('button', { name: 'Ver detalles de Base' }),
        );
        expect(onOpen).toHaveBeenCalledWith('base');
    });
});

describe('NodeDetailPanel', () => {
    it('shows progress, what the track needs and unlocks, its lessons and where to continue', () => {
        render(
            <NodeDetailPanel
                roadmapSlug="ai-engineer"
                policy="ADVISORY"
                track={tracks[0]}
                tracks={tracks}
                edges={edges}
                onClose={() => {}}
            />,
        );

        const panel = screen.getByRole('dialog', { name: 'Base' });
        expect(within(panel).getByText('1 de 2 lecciones')).toBeInTheDocument();
        expect(
            within(panel).getByText('Es un punto de partida.'),
        ).toBeInTheDocument();
        expect(within(panel).getByText('Desbloquea')).toBeInTheDocument();
        expect(within(panel).getByText('Siguiente')).toBeInTheDocument();
        expect(
            within(panel).getByRole('link', { name: 'Primera' }),
        ).toHaveAttribute('href', '/lessons/primera');
        expect(
            within(panel).getByRole('link', { name: /Continuar: Segunda/ }),
        ).toHaveAttribute('href', '/lessons/segunda');
        expect(
            within(panel).getByRole('link', { name: 'Ver track' }),
        ).toHaveAttribute('href', '/roadmaps/ai-engineer/tracks/base');
    });

    it('explains a locked track with its pending requirement', () => {
        render(
            <NodeDetailPanel
                roadmapSlug="ai-engineer"
                policy="ADVISORY"
                track={tracks[1]}
                tracks={tracks}
                edges={edges}
                onClose={() => {}}
            />,
        );

        const panel = screen.getByRole('dialog', { name: 'Siguiente' });
        expect(within(panel).getByText('Base al 100 %')).toBeInTheDocument();
        expect(
            within(panel).getByRole('link', {
                name: 'Base (llevas 50 %, se requiere 100 %)',
            }),
        ).toBeInTheDocument();
    });

    it('stays closed without a selection', () => {
        render(
            <NodeDetailPanel
                roadmapSlug="ai-engineer"
                policy="ADVISORY"
                track={null}
                tracks={tracks}
                edges={edges}
                onClose={() => {}}
            />,
        );

        expect(screen.queryByRole('dialog')).toBeNull();
    });
});
