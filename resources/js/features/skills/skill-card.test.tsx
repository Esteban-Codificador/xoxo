import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { BlockerNotice } from '@/features/progress/blocker-notice';
import { SkillCard } from './skill-card';
import type { SkillSummary } from './progress';

const git: SkillSummary = {
    slug: 'git',
    name: 'Git',
    description: 'Control de versiones con Git.',
    difficulty: 'BEGINNER',
    progress: {
        state: 'IN_PROGRESS',
        progress: 50,
        completed: 1,
        total: 2,
        blockers: [],
    },
};

describe('SkillCard', () => {
    it('links the skill and says how far the learner is', () => {
        render(
            <ol>
                <SkillCard skill={git} />
            </ol>,
        );

        expect(screen.getByRole('link', { name: 'Ver Git' })).toHaveAttribute(
            'href',
            '/skills/git',
        );
        expect(
            screen.getByRole('progressbar', { name: 'Progreso en Git: 50 %' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('1 de 2 lecciones completadas · Principiante'),
        ).toBeVisible();
    });

    it('counts a single lesson in the singular', () => {
        render(
            <ol>
                <SkillCard
                    skill={{
                        ...git,
                        progress: { ...git.progress, completed: 0, total: 1 },
                    }}
                />
            </ol>,
        );

        expect(
            screen.getByText('0 de 1 lección completada · Principiante'),
        ).toBeVisible();
    });

    it('says when no lesson develops the skill yet', () => {
        render(
            <ol>
                <SkillCard
                    skill={{
                        ...git,
                        progress: { ...git.progress, completed: 0, total: 0 },
                    }}
                />
            </ol>,
        );

        expect(
            screen.getByText('Sin lecciones publicadas todavía · Principiante'),
        ).toBeVisible();
    });
});

describe('BlockerNotice for skills', () => {
    it('only advises, and links the skill to develop first', () => {
        render(
            <BlockerNotice
                blockers={[
                    {
                        type: 'skill',
                        slug: 'python',
                        title: 'Python',
                        progress: 20,
                        required: 80,
                    },
                ]}
                roadmapSlug="ai-engineer"
                strict={false}
                scope="skill"
            />,
        );

        expect(
            screen.getByText('Antes de esta skill conviene avanzar en'),
        ).toBeVisible();
        expect(
            screen.getByRole('link', {
                name: 'Python (llevas 20 %, se requiere 80 %)',
            }),
        ).toHaveAttribute('href', '/skills/python');
    });
});
