import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import type { Changes } from './lesson-changes';
import { LessonChanges } from './lesson-changes';

const changes: Changes = {
    fields: [
        {
            field: 'title',
            rows: [
                {
                    type: 'removed',
                    old: 1,
                    new: null,
                    segments: [{ text: 'Commits', changed: false }],
                },
                {
                    type: 'added',
                    old: null,
                    new: 1,
                    segments: [
                        { text: 'Commits', changed: false },
                        { text: ' y staging', changed: true },
                    ],
                },
            ],
        },
    ],
    attributes: [
        { field: 'difficulty', before: 'BEGINNER', after: 'INTERMEDIATE' },
        { field: 'estimated_minutes', before: 30, after: 90 },
    ],
    body: [
        { type: 'skipped', count: 12 },
        {
            type: 'same',
            old: 13,
            new: 13,
            segments: [{ text: '## Concepto', changed: false }],
        },
        {
            type: 'added',
            old: null,
            new: 14,
            segments: [{ text: 'Un párrafo nuevo.', changed: false }],
        },
        { type: 'skipped', count: 1 },
    ],
};

describe('LessonChanges', () => {
    it('labels fields and shows attributes as before and after', () => {
        render(<LessonChanges changes={changes} />);

        expect(
            screen.getByRole('heading', { name: 'Título' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Dificultad')).toBeInTheDocument();
        expect(screen.getByText('Principiante').tagName).toBe('DEL');
        expect(screen.getByText('Intermedio').tagName).toBe('INS');
        expect(screen.getByText('1 h 30 min').tagName).toBe('INS');
    });

    it('marks changed words and announces added and removed lines', () => {
        const { container } = render(<LessonChanges changes={changes} />);

        expect(screen.getByText('y staging', { exact: false }).tagName).toBe(
            'INS',
        );
        expect(
            screen.getAllByText('Eliminado:', { exact: false }),
        ).toHaveLength(1);
        expect(screen.getAllByText('Añadido:', { exact: false })).toHaveLength(
            2,
        );
        expect(container.querySelectorAll('ol')[1]?.textContent).toContain(
            '12 líneas sin cambios',
        );
        expect(screen.getByText('1 línea sin cambios')).toBeInTheDocument();
    });

    it('says so when nothing changed', () => {
        render(
            <LessonChanges
                changes={{ fields: [], attributes: [], body: [] }}
            />,
        );

        expect(
            screen.getByText('Sin cambios en el contenido.'),
        ).toBeInTheDocument();
    });
});
