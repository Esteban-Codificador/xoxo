import { render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Recommendation } from './recommendation-list';
import { RecommendationList, recommendationText } from './recommendation-list';

const resume: Recommendation = {
    reason: 'CONTINUE',
    subject: {
        type: 'lesson',
        slug: 'ramas',
        title: 'Ramas, merge y rebase',
        track: 'Fundamentos de computación',
    },
    params: { viewed_at: '2026-09-28T10:00:00+00:00' },
    priority: 1,
};

const unlock: Recommendation = {
    reason: 'UNLOCK',
    subject: {
        type: 'track',
        slug: 'orientacion',
        title: 'Orientación',
        track: null,
    },
    params: { next: 'Fundamentos de computación', progress: 33, required: 100 },
    priority: 2,
};

describe('recommendationText', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-09-30T12:00:00+00:00'));
    });

    afterEach(() => vi.useRealTimers());

    it('explains each rule in a sentence', () => {
        expect(recommendationText(resume)).toEqual({
            eyebrow: 'Continúa donde lo dejaste',
            reason: 'La abriste por última vez anteayer.',
            action: 'Continuar',
        });
        expect(recommendationText(unlock)).toEqual({
            eyebrow: 'Para desbloquear Fundamentos de computación',
            reason: 'Antes de continuar con Fundamentos de computación, completa Orientación: llevas 33 %, se requiere 100 %.',
            action: 'Ir al track',
        });
        expect(
            recommendationText({
                ...resume,
                reason: 'NEXT_IN_TRACK',
                params: {},
            }).reason,
        ).toBe(
            'Es la siguiente lección disponible de Fundamentos de computación.',
        );
    });
});

describe('RecommendationList', () => {
    it('links lessons and tracks, the first one leading', () => {
        render(
            <RecommendationList
                roadmapSlug="ai-engineer"
                recommendations={[resume, unlock]}
            />,
        );

        const items = within(
            screen.getByRole('region', { name: 'Recomendado para ti' }),
        ).getAllByRole('listitem');
        expect(items).toHaveLength(2);
        expect(
            within(items[0]).getByRole('link', {
                name: 'Continuar: Ramas, merge y rebase',
            }),
        ).toHaveAttribute('href', '/lessons/ramas');
        expect(
            within(items[1]).getByRole('link', {
                name: 'Ir al track: Orientación',
            }),
        ).toHaveAttribute('href', '/roadmaps/ai-engineer/tracks/orientacion');
    });

    it('shows nothing without recommendations', () => {
        const { container } = render(
            <RecommendationList
                roadmapSlug="ai-engineer"
                recommendations={[]}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});
