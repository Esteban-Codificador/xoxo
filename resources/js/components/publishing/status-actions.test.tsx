import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { StatusActions } from './status-actions';

const put = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({ router: { put } }));

describe('StatusActions', () => {
    it('shows only the allowed changes and confirms before sending one', async () => {
        render(
            <StatusActions
                entity="track"
                name="Fundamentos"
                current="PUBLISHED"
                actions={['DRAFT', 'ARCHIVED']}
                url="/admin/tracks/1/status"
            />,
        );

        expect(screen.queryByRole('button', { name: 'Publicar' })).toBeNull();

        await userEvent.click(
            screen.getByRole('button', { name: 'Pasar a borrador' }),
        );
        const dialog = screen.getByRole('dialog', {
            name: '¿Pasar «Fundamentos» a borrador?',
        });
        expect(dialog).toHaveTextContent(
            'Los estudiantes dejarán de ver el track y todas sus lecciones. Su progreso se conserva.',
        );
        expect(put).not.toHaveBeenCalled();

        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Pasar a borrador' }),
        );
        expect(put).toHaveBeenCalledWith(
            '/admin/tracks/1/status',
            { status: 'DRAFT' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('warns that unpublishing the roadmap hides all its content', async () => {
        render(
            <StatusActions
                entity="roadmap"
                name="AI Engineer"
                current="PUBLISHED"
                actions={['DRAFT', 'ARCHIVED']}
                url="/admin/roadmaps/1/status"
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Pasar a borrador' }),
        );

        expect(
            screen.getByRole('dialog', {
                name: '¿Pasar «AI Engineer» a borrador?',
            }),
        ).toHaveTextContent(
            'Los estudiantes dejarán de ver el roadmap y todo su contenido: tracks, módulos y lecciones.',
        );
    });

    it('calls leaving ARCHIVED a restore', () => {
        render(
            <StatusActions
                entity="module"
                name="Ramas"
                current="ARCHIVED"
                actions={['DRAFT']}
                url="/admin/modules/1/status"
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Restaurar como borrador' }),
        ).toBeInTheDocument();
    });

    it('renders nothing without allowed changes', () => {
        const { container } = render(
            <StatusActions
                entity="track"
                name="Base"
                current="DRAFT"
                actions={[]}
                url="/admin/tracks/1/status"
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});
