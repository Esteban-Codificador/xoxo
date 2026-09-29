import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { BlockerNotice } from './blocker-notice';
import { CompleteLesson } from './complete-lesson';
import type { Blocker, LessonProgress } from './types';

const blockers: Blocker[] = [
    {
        type: 'lesson',
        slug: 'commits',
        title: 'Commits',
        progress: null,
        required: null,
    },
    {
        type: 'track',
        slug: 'python',
        title: 'Python',
        progress: 45,
        required: 80,
    },
];

const progress: LessonProgress = {
    state: 'IN_PROGRESS',
    blockers: [],
    policy: 'ADVISORY',
    can_progress: true,
    starts_on_open: false,
    completed_at: null,
};

describe('BlockerNotice', () => {
    it('recommends the order under ADVISORY and explains how far the learner is', () => {
        render(
            <BlockerNotice
                blockers={blockers}
                roadmapSlug="ai-engineer"
                strict={false}
                scope="lesson"
            />,
        );

        expect(screen.getByRole('note')).toHaveTextContent(
            'Antes de esta lección conviene completar',
        );
        expect(screen.getByRole('link', { name: 'Commits' })).toHaveAttribute(
            'href',
            '/lessons/commits',
        );
        expect(
            screen.getByRole('link', {
                name: 'Python (llevas 45 %, se requiere 80 %)',
            }),
        ).toHaveAttribute('href', '/roadmaps/ai-engineer/tracks/python');
        expect(screen.getByText(/no lo exige/)).toBeInTheDocument();
    });

    it('says the order is required under STRICT', () => {
        render(
            <BlockerNotice
                blockers={blockers}
                roadmapSlug="ai-engineer"
                strict
                scope="lesson"
            />,
        );

        expect(screen.getByRole('note')).toHaveTextContent(
            'Esta ruta exige completar antes',
        );
        expect(screen.queryByText(/no lo exige/)).toBeNull();
    });

    it('renders nothing without blockers', () => {
        const { container } = render(
            <BlockerNotice
                blockers={[]}
                roadmapSlug="x"
                strict={false}
                scope="track"
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});

describe('CompleteLesson', () => {
    it('offers to complete a lesson in progress', () => {
        render(
            <CompleteLesson
                lessonSlug="commits"
                progress={progress}
                next={null}
            />,
        );

        const button = screen.getByRole('button', {
            name: 'Marcar como completada',
        });
        expect(button).toBeEnabled();
        expect(button.closest('form')).toHaveAttribute(
            'action',
            '/lessons/commits/complete',
        );
    });

    it('disables completion when a STRICT roadmap blocks it', () => {
        render(
            <CompleteLesson
                lessonSlug="commits"
                progress={{
                    ...progress,
                    state: 'LOCKED',
                    policy: 'STRICT',
                    can_progress: false,
                }}
                next={null}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Marcar como completada' }),
        ).toBeDisabled();
    });

    it('confirms a completed lesson, lets the learner undo it and points to the next one', () => {
        render(
            <CompleteLesson
                lessonSlug="commits"
                progress={{
                    ...progress,
                    state: 'COMPLETED',
                    completed_at: '2026-09-29T10:00:00+00:00',
                }}
                next={{ slug: 'ramas', title: 'Ramas' }}
            />,
        );

        expect(
            screen.getByText(
                /Completaste esta lección el 29 de septiembre de 2026/,
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Desmarcar' }).closest('form'),
        ).toHaveAttribute('action', '/lessons/commits/complete?_method=DELETE');
        expect(
            screen.getByRole('link', { name: /Siguiente: Ramas/ }),
        ).toHaveAttribute('href', '/lessons/ramas');
    });

    it('does not offer to undo a mastered lesson', () => {
        render(
            <CompleteLesson
                lessonSlug="commits"
                progress={{
                    ...progress,
                    state: 'MASTERED',
                    completed_at: '2026-09-29T10:00:00+00:00',
                }}
                next={null}
            />,
        );

        expect(screen.queryByRole('button', { name: 'Desmarcar' })).toBeNull();
    });
});
